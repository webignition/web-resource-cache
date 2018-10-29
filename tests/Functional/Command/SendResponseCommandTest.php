<?php

namespace App\Tests\Functional\Command;

use App\Command\SendResponseCommand;
use App\Entity\CachedResource;
use App\Model\RequestIdentifier;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\RebuildableDecoratedResponse;
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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use webignition\HttpHeaders\Headers;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class SendResponseCommandTest extends AbstractFunctionalTestCase
{
    /**
     * @var SendResponseCommand
     */
    private $command;

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

        $this->command = self::$container->get(SendResponseCommand::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->cachedResourceFactory = self::$container->get(CachedResourceFactory::class);
        $this->cachedResourceManager = self::$container->get(CachedResourceManager::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
        $this->callbackFactory = self::$container->get(CallbackFactory::class);
        $this->callbackManager = self::$container->get(CallbackManager::class);
        $this->httpRequestAsserter = self::$container->get(HttpRequestAsserter::class);
    }

    /**
     * @throws \Exception
     */
    public function testRunResponseInvalid()
    {
        $input = new ArrayInput([
            'response-json' => '',
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(SendResponseCommand::RETURN_CODE_RESPONSE_INVALID, $returnCode);
    }

    /**
     * @throws \Exception
     */
    public function testRunResourceNotFound()
    {
        $input = new ArrayInput([
            'response-json' => json_encode(
                new RebuildableDecoratedResponse(new SuccessResponse('invalid-request-hash'))
            ),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(SendResponseCommand::RETURN_CODE_RESOURCE_NOT_FOUND, $returnCode);
    }

    /**
     * @dataProvider runSuccessForFailureResponseDataProvider
     *
     * @param array $callbacks
     * @param string $responseJson
     * @param array $expectedRequestUrls
     * @param array $expectedRequestData
     *
     * @throws \Exception
     */
    public function testRunSuccessForFailureResponseVerifyRequestData(
        array $callbacks,
        string $responseJson,
        array $expectedRequestUrls,
        array $expectedRequestData
    ) {
        $this->httpMockHandler->appendFixtures(array_fill(0, count($expectedRequestUrls), new Response()));

        $retrieveRequest = $this->createRetrieveRequest('http://example.com/', new Headers());
        $requestHash = $retrieveRequest->getRequestHash();

        foreach ($callbacks as $callbackData) {
            $callbackData['requestHash'] = str_replace('{{ requestHash }}', $requestHash, $callbackData['requestHash']);
            $this->createCallback($callbackData['requestHash'], $callbackData['url']);
        }

        $responseJson = str_replace('{{ requestHash }}', $requestHash, $responseJson);

        if (!empty($expectedRequestData)) {
            $expectedRequestData['request_id'] = str_replace(
                '{{ requestHash }}',
                $retrieveRequest->getRequestHash(),
                $expectedRequestData['request_id']
            );
        }

        $input = new ArrayInput([
            'response-json' => $responseJson,
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(SendResponseCommand::RETURN_CODE_OK, $returnCode);

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
                'responseJson' => json_encode(
                    new RebuildableDecoratedResponse(new UnknownFailureResponse('{{ requestHash }}'))
                ),
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
                'responseJson' => json_encode(
                    new RebuildableDecoratedResponse(new UnknownFailureResponse('{{ requestHash }}'))
                ),
                'expectedRequestUrls' => [],
                'expectedRequestData' => [],
            ],
            'unknown failure response, single matching callback' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => '{{ requestHash }}',
                    ],
                ],
                'responseJson' => json_encode(
                    new RebuildableDecoratedResponse(new UnknownFailureResponse('{{ requestHash }}'))
                ),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => '{{ requestHash }}',
                    'status' => 'failed',
                    'failure_type' => 'unknown',
                ],
            ],
            'http 404 failure response, single matching callback' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => '{{ requestHash }}',
                    ],
                ],
                'responseJson' => json_encode(
                    new RebuildableDecoratedResponse(new KnownFailureResponse(
                        '{{ requestHash }}',
                        KnownFailureResponse::TYPE_HTTP,
                        404
                    ))
                ),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => '{{ requestHash }}',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ],
            ],
            'curl 28 failure response, single matching callback' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => '{{ requestHash }}',
                    ],
                ],
                'responseJson' => json_encode(
                    new RebuildableDecoratedResponse(new KnownFailureResponse(
                        '{{ requestHash }}',
                        KnownFailureResponse::TYPE_CONNECTION,
                        28
                    ))
                ),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => '{{ requestHash }}',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                ],
            ],
            'unknown failure response, multiple matching callbacks' => [
                'callbacks' => [
                    [
                        'url' => 'http://callback1.example.com',
                        'requestHash' => '{{ requestHash }}',
                    ],
                    [
                        'url' => 'http://callback2.example.com',
                        'requestHash' => '{{ requestHash }}',
                    ],
                    [
                        'url' => 'http://callback3.example.com',
                        'requestHash' => 'non-matching-request-hash',
                    ],
                ],
                'responseJson' => json_encode(
                    new RebuildableDecoratedResponse(new UnknownFailureResponse('{{ requestHash }}'))
                ),
                'expectedRequestUrls' => [
                    'http://callback1.example.com',
                    'http://callback2.example.com',
                ],
                'expectedRequestData' => [
                    'request_id' => '{{ requestHash }}',
                    'status' => 'failed',
                    'failure_type' => 'unknown',
                ],
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function testRunSuccessForSuccessResponseVerifyRequestData()
    {
        $this->httpMockHandler->appendFixtures([
            new Response(),
        ]);

        $retrieveRequest = $this->createRetrieveRequest('http://example.com/', new Headers());
        $requestHash = $retrieveRequest->getRequestHash();

        $callbackUrl = 'http://callback.example.com/';
        $this->createCallback($requestHash, $callbackUrl);

        $response = new RebuildableDecoratedResponse(new SuccessResponse($requestHash));

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

        $input = new ArrayInput([
            'response-json' => json_encode($response),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(SendResponseCommand::RETURN_CODE_OK, $returnCode);

        $this->httpRequestAsserter->assertSenderRequest(
            $this->httpHistoryContainer->getLastRequest(),
            $callbackUrl,
            $expectedRequestData
        );
    }

    /**
     * @throws \Exception
     */
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

        $response = new RebuildableDecoratedResponse(new SuccessResponse($requestHash));

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

        $input = new ArrayInput([
            'response-json' => json_encode($response),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

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

        $cachedResource = $this->cachedResourceFactory->create($retrieveRequest, $httpResponse);
        $this->cachedResourceManager->update($cachedResource);

        return $cachedResource;
    }

    private function createCallback(string $requestHash, string $url)
    {
        $this->callbackManager->persist($this->callbackFactory->create($requestHash, $url));
    }
}
