<?php

namespace App\Tests\Functional\MessageHandler;

use App\Command\SendResponseCommand;
use App\Entity\CachedResource;
use App\Message\SendResponse;
use App\MessageHandler\SendResponseHandler;
use App\Model\RequestIdentifier;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Model\RetrieveRequest;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\CallbackFactory;
use App\Services\CallbackManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Tests\Services\Asserter\HttpRequestAsserter;
use App\Tests\Services\HttpMockHandler;
use GuzzleHttp\Psr7\Response as HttpResponse;
use GuzzleHttp\Psr7\Response;
use webignition\HttpHeaders\Headers;
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

    public function testInvokeNoCachedResource()
    {
        $successResponse = new SuccessResponse('invalid-request-hash');

        $message = new SendResponse($successResponse);

        $this->handler->__invoke($message);

        $this->assertCount(0, $this->httpHistoryContainer);
    }

    /**
     * @dataProvider runSuccessForFailureResponseDataProvider
     *
     * @param array $callbacks
     * @param SendResponse $sendResponseMessage
     * @param array $expectedRequestUrls
     * @param array $expectedRequestData
     */
    public function testRunSuccessForFailureResponseVerifyRequestData(
        array $callbacks,
        SendResponse $sendResponseMessage,
        array $expectedRequestUrls,
        array $expectedRequestData
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
    }

    public function runSuccessForFailureResponseDataProvider(): array
    {
        return [
            'unknown failure response, no callbacks' => [
                'callbacks' => [],
                'sendResponseMessage' => new SendResponse(new UnknownFailureResponse('request_hash')),
                'expectedRequestUrls' => [],
                'expectedRequestData' => [],
            ],
            'unknown failure response, no matching callbacks' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => 'no-matching-request-hash',
                    ],
                ],
                'sendResponseMessage' => new SendResponse(new UnknownFailureResponse('request_hash')),
                'expectedRequestUrls' => [],
                'expectedRequestData' => [],
            ],
            'unknown failure response, single matching callback' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => 'request_hash',
                    ],
                ],
                'sendResponseMessage' => new SendResponse(new UnknownFailureResponse('request_hash')),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => 'unknown',
                ],
            ],
            'http 404 failure response, single matching callback' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => 'request_hash',
                    ],
                ],
                'sendResponseMessage' => new SendResponse(new KnownFailureResponse(
                    'request_hash',
                    KnownFailureResponse::TYPE_HTTP,
                    404
                )),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ],
            ],
            'curl 28 failure response, single matching callback' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => 'request_hash',
                    ],
                ],
                'sendResponseMessage' => new SendResponse(new KnownFailureResponse(
                    'request_hash',
                    KnownFailureResponse::TYPE_CONNECTION,
                    28
                )),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                ],
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
                'sendResponseMessage' => new SendResponse(new UnknownFailureResponse('request_hash')),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                    'http://callback2.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => 'unknown',
                ],
            ],
        ];
    }

    public function testRunSuccessForSuccessResponseVerifyRequestData()
    {
        $this->httpMockHandler->appendFixtures([
            new Response(),
        ]);

        $retrieveRequest = $this->createRetrieveRequest('http://example.com/', new Headers());
        $requestHash = $retrieveRequest->getRequestHash();

        $callbackUrl = 'http://callback.example.com/';
        $this->createCallback($requestHash, $callbackUrl);

        $successResponse = new SuccessResponse($requestHash);
        $sendResponseMessage = new SendResponse($successResponse);

        $cachedResource = $this->createCachedResource(
            [
                'content-type' => 'text/plain',
            ],
            'resource content',
            $retrieveRequest
        );

        $expectedRequestData = [
            'request_id' => $retrieveRequest->getRequestHash(),
            'status' => SuccessResponse::STATUS_SUCCESS,
            'headers' => $cachedResource->getHeaders()->toArray(),
            'content' => $cachedResource->getBody(),
        ];

        $returnCode = $this->handler->__invoke($sendResponseMessage);

        $this->assertEquals(SendResponseCommand::RETURN_CODE_OK, $returnCode);

        $this->httpRequestAsserter->assertSenderRequest(
            $this->httpHistoryContainer->getLastRequest(),
            $callbackUrl,
            $expectedRequestData
        );
    }

    public function testSendSuccessMultipleCallbackUrls()
    {
        $callbackUrls = [
            'http://callback1.example.com/',
            'http://callback2.example.com/',
            'http://callback3.example.com/'
        ];

        $this->httpMockHandler->appendFixtures(array_fill(0, count($callbackUrls), new Response()));

        $retrieveRequest = $this->createRetrieveRequest('http://example.com/', new Headers());
        $requestHash = $retrieveRequest->getRequestHash();

        foreach ($callbackUrls as $callbackUrl) {
            $this->createCallback($requestHash, $callbackUrl);
        }

        $cachedResource = $this->createCachedResource(
            [
                'content-type' => 'text/plain',
            ],
            'resource content',
            $retrieveRequest
        );

        $expectedRequestData = [
            'request_id' => $retrieveRequest->getRequestHash(),
            'status' => SuccessResponse::STATUS_SUCCESS,
            'headers' => $cachedResource->getHeaders()->toArray(),
            'content' => $cachedResource->getBody(),
        ];

        $successResponse = new SuccessResponse($requestHash);
        $sendResponseMessage = new SendResponse($successResponse);

        $returnCode = $this->handler->__invoke($sendResponseMessage);

        $this->assertEquals(SendResponseCommand::RETURN_CODE_OK, $returnCode);

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

    private function createRetrieveRequest(string $url, Headers $headers): RetrieveRequest
    {
        $requestIdentifier = new RequestIdentifier($url, $headers);
        $retrieveRequest = new RetrieveRequest($requestIdentifier->getHash(), $url, $headers);

        return $retrieveRequest;
    }

    private function createCachedResource(
        array $httpResponseHeaders,
        string $httpResponseBody,
        RetrieveRequest $retrieveRequest
    ): CachedResource {
        $httpResponse = new HttpResponse(200, $httpResponseHeaders, $httpResponseBody);

        $cachedResource = $this->cachedResourceFactory->create(
            $retrieveRequest->getRequestHash(),
            $retrieveRequest->getUrl(),
            $httpResponse
        );
        $this->cachedResourceManager->update($cachedResource);

        return $cachedResource;
    }

    private function createCallback(string $requestHash, string $url)
    {
        $this->callbackManager->persist($this->callbackFactory->create($requestHash, $url));
    }
}
