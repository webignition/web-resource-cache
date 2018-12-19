<?php

namespace App\Tests\Functional\MessageHandler;

use App\Entity\CachedResource;
use App\Entity\Callback as CallbackEntity;
use App\Exception\InvalidResponseDataException;
use App\Message\SendResponse;
use App\MessageHandler\SendResponseHandler;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\CallbackFactory;
use App\Services\CallbackManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Tests\Services\Asserter\HttpRequestAsserter;
use App\Tests\Services\HttpMockHandler;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response as HttpResponse;
use GuzzleHttp\Psr7\Response;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class SendResponseHandlerTest extends AbstractFunctionalTestCase
{
    /**
     * @var SendResponseHandler
     */
    private $handler;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var CachedResourceFactory
     */
    private $cachedResourceFactory;

    /**
     * @var CachedResourceManager
     */
    private $cachedResourceManager;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    /**
     * @var CallbackFactory
     */
    private $callbackFactory;

    /**
     * @var CallbackManager
     */
    private $callbackManager;

    /**
     * @var HttpRequestAsserter
     */
    private $httpRequestAsserter;

    protected function setUp()
    {
        parent::setUp();

        $this->handler = self::$container->get(SendResponseHandler::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->cachedResourceFactory = self::$container->get(CachedResourceFactory::class);
        $this->cachedResourceManager = self::$container->get(CachedResourceManager::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
        $this->callbackFactory = self::$container->get(CallbackFactory::class);
        $this->callbackManager = self::$container->get(CallbackManager::class);
        $this->httpRequestAsserter = self::$container->get(HttpRequestAsserter::class);
    }

    /**
     * @throws InvalidResponseDataException
     */
    public function testInvokeInvalidResponse()
    {
        $this->expectException(InvalidResponseDataException::class);

        $this->handler->__invoke(new SendResponse([]));
    }

    /**
     * @throws InvalidResponseDataException
     */
    public function testInvokeNoCachedResource()
    {
        $successResponse = new SuccessResponse('invalid-request-hash');

        $message = new SendResponse($successResponse->jsonSerialize());

        $this->handler->__invoke($message);

        $this->assertCount(0, $this->httpHistoryContainer);
    }

    /**
     * @throws InvalidResponseDataException
     */
    public function testRunSuccessNoCallbacks()
    {
        $unknownFailureResponse = new UnknownFailureResponse('request_hash');
        $sendResponseMessage = new SendResponse($unknownFailureResponse->jsonSerialize());

        $this->handler->__invoke($sendResponseMessage);

        $this->assertEmpty($this->httpHistoryContainer->getRequests());
    }

    /**
     * @dataProvider runSuccessForFailureResponseDataProvider
     *
     * @param array $callbacks
     * @param SendResponse $sendResponseMessage
     * @param array $expectedRequestUrls
     * @param array $expectedRequestData
     * @param array $expectedRemainingCallbacks
     *
     * @throws InvalidResponseDataException
     */
    public function testRunSuccessForFailureResponseVerifyRequestData(
        array $callbacks,
        SendResponse $sendResponseMessage,
        array $expectedRequestUrls,
        array $expectedRequestData,
        array $expectedRemainingCallbacks
    ) {
        $this->httpMockHandler->appendFixtures(array_fill(0, count($expectedRequestUrls), new Response()));

        foreach ($callbacks as $callbackData) {
            $this->createCallback($callbackData['requestHash'], $callbackData['url']);
        }

        $this->handler->__invoke($sendResponseMessage);

        $requestUrls = $this->httpHistoryContainer->getRequestUrlsAsStrings();
        $this->assertEquals($expectedRequestUrls, $requestUrls);

        $requests = $this->httpHistoryContainer->getRequests();

        if (empty($expectedRequestData)) {
            $this->assertEmpty($requests);
        } else {
            foreach ($requests as $requestIndex => $request) {
                $expectedRequestUrl = $expectedRequestUrls[$requestIndex];

                $this->httpRequestAsserter->assertSenderRequest(
                    $request,
                    $expectedRequestUrl,
                    $expectedRequestData
                );
            }
        }

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $callbackRepository = $entityManager->getRepository(CallbackEntity::class);

        $remainingCallbacks = $callbackRepository->findAll();

        if (empty($expectedRemainingCallbacks)) {
            $this->assertEmpty($remainingCallbacks);
        } else {
            $this->assertCount(count($expectedRemainingCallbacks), $remainingCallbacks);

            foreach ($expectedRemainingCallbacks as $expectedRemainingCallback) {
                $callback = $callbackRepository->findOneBy($expectedRemainingCallback);

                $this->assertInstanceOf(CallbackEntity::class, $callback);
            }
        }
    }

    public function runSuccessForFailureResponseDataProvider(): array
    {
        $unknownFailureResponse = new UnknownFailureResponse('request_hash');

        return [
            'unknown failure response, no matching callbacks' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => 'no-matching-request-hash',
                    ],
                ],
                'sendResponseMessage' => new SendResponse($unknownFailureResponse->jsonSerialize()),
                'expectedRequestUrls' => [],
                'expectedRequestData' => [],
                'expectedRemainingCallbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => 'no-matching-request-hash',
                    ],
                ],
            ],
            'unknown failure response, single matching callback' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => 'request_hash',
                    ],
                ],
                'sendResponseMessage' => new SendResponse($unknownFailureResponse->jsonSerialize()),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => 'unknown',
                ],
                'expectedRemainingCallbacks' => [],
            ],
            'http 404 failure response, single matching callback' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => 'request_hash',
                    ],
                ],
                'sendResponseMessage' => new SendResponse((new KnownFailureResponse(
                    'request_hash',
                    KnownFailureResponse::TYPE_HTTP,
                    404
                ))->jsonSerialize()),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                    'context' => [],
                ],
                'expectedRemainingCallbacks' => [],
            ],
            'curl 28 failure response, single matching callback' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => 'request_hash',
                    ],
                ],
                'sendResponseMessage' => new SendResponse((new KnownFailureResponse(
                    'request_hash',
                    KnownFailureResponse::TYPE_CONNECTION,
                    28
                ))->jsonSerialize()),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                    'context' => [],
                ],
                'expectedRemainingCallbacks' => [],
            ],
            'unknown failure response, multiple matching callbacks' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => 'request_hash',
                    ],
                    [
                        'url' => 'http://callback2.example.com',
                        'requestHash' => 'request_hash',
                    ],
                    [
                        'url' => 'http://callback3.example.com',
                        'requestHash' => 'non-matching-request-hash',
                    ],
                ],
                'sendResponseMessage' => new SendResponse($unknownFailureResponse->jsonSerialize()),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                    'http://callback2.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => 'unknown',
                ],
                'expectedRemainingCallbacks' => [
                    [
                        'url' => 'http://callback3.example.com',
                        'requestHash' => 'non-matching-request-hash',
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws InvalidResponseDataException
     */
    public function testRunSuccessForSuccessResponseVerifyRequestData()
    {
        $this->httpMockHandler->appendFixtures([
            new Response(),
        ]);

        $url = 'http://example.com/';
        $requestHash = 'request_hash';

        $callbackUrl = 'http://callback.example.com/';
        $this->createCallback($requestHash, $callbackUrl);

        $successResponse = new SuccessResponse($requestHash);
        $sendResponseMessage = new SendResponse($successResponse->jsonSerialize());

        $cachedResource = $this->createCachedResource(
            [
                'content-type' => 'text/plain',
            ],
            'resource content',
            $requestHash,
            $url
        );

        $expectedRequestData = [
            'request_id' => $requestHash,
            'status' => SuccessResponse::STATUS_SUCCESS,
            'headers' => $cachedResource->getHeaders()->toArray(),
            'content' => $cachedResource->getSerializedBody(),
        ];

        $this->handler->__invoke($sendResponseMessage);

        $this->httpRequestAsserter->assertSenderRequest(
            $this->httpHistoryContainer->getLastRequest(),
            $callbackUrl,
            $expectedRequestData
        );
    }

    /**
     * @throws InvalidResponseDataException
     */
    public function testSendSuccessMultipleCallbackUrls()
    {
        $callbackUrls = [
            'http://callback1.example.com/',
            'http://callback2.example.com/',
            'http://callback3.example.com/'
        ];

        $this->httpMockHandler->appendFixtures(array_fill(0, count($callbackUrls), new Response()));

        $url = 'http://example.com/';
        $requestHash = 'request_hash';

        foreach ($callbackUrls as $callbackUrl) {
            $this->createCallback($requestHash, $callbackUrl);
        }

        $cachedResource = $this->createCachedResource(
            [
                'content-type' => 'text/plain',
            ],
            'resource content',
            $requestHash,
            $url
        );

        $expectedRequestData = [
            'request_id' => $requestHash,
            'status' => SuccessResponse::STATUS_SUCCESS,
            'headers' => $cachedResource->getHeaders()->toArray(),
            'content' => $cachedResource->getSerializedBody(),
        ];

        $successResponse = new SuccessResponse($requestHash);
        $sendResponseMessage = new SendResponse($successResponse->jsonSerialize());

        $this->handler->__invoke($sendResponseMessage);

        $requests = $this->httpHistoryContainer->getRequests();

        foreach ($requests as $requestIndex => $request) {
            $expectedRequestUrl = $callbackUrls[$requestIndex];

            $this->httpRequestAsserter->assertSenderRequest(
                $request,
                $expectedRequestUrl,
                $expectedRequestData
            );
        }
    }

    private function createCachedResource(
        array $httpResponseHeaders,
        string $httpResponseBody,
        string $requestHash,
        string $url
    ): CachedResource {
        $httpResponse = new HttpResponse(200, $httpResponseHeaders, $httpResponseBody);

        $cachedResource = $this->cachedResourceFactory->create(
            $requestHash,
            $url,
            $httpResponse
        );
        $this->cachedResourceManager->update($cachedResource);

        return $cachedResource;
    }

    private function createCallback(string $requestHash, string $url): CallbackEntity
    {
        $callback = $this->callbackFactory->create($requestHash, $url, false);
        $this->callbackManager->persist($callback);

        return $callback;
    }
}
