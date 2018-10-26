<?php

namespace App\Tests\Functional\Command;

use App\Command\SendResponseCommand;
use App\Entity\CachedResource;
use App\Entity\RetrieveRequest;
use App\Model\RequestIdentifier;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\RebuildableDecoratedResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\ResqueQueueService;
use App\Services\RetrieveRequestManager;
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
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var RetrieveRequestManager
     */
    private $retrieveRequestManager;

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

    protected function setUp()
    {
        parent::setUp();

        $this->clearRedis();

        $this->command = self::$container->get(SendResponseCommand::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->resqueQueueService = self::$container->get(ResqueQueueService::class);
        $this->retrieveRequestManager = self::$container->get(RetrieveRequestManager::class);
        $this->cachedResourceFactory = self::$container->get(CachedResourceFactory::class);
        $this->cachedResourceManager = self::$container->get(CachedResourceManager::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    /**
     * @dataProvider runResponseNotFoundDataProvider
     *
     * @param string $responseJson
     *
     * @throws \Exception
     */
    public function testRunResponseNotFound(string $responseJson)
    {
        $input = new ArrayInput([
            'response-json' => $responseJson,
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(SendResponseCommand::RETURN_CODE_RESPONSE_NOT_FOUND, $returnCode);
    }

    public function runResponseNotFoundDataProvider(): array
    {
        return [
            'empty json' => [
                'responseJson' => '',
            ],
            'success response invalid request_id' => [
                'responseJson' => json_encode([
                    'request_id' => 'invalid-request-hash',
                    'class' => SuccessResponse::class,
                ]),
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function testRunResourceNotFound()
    {
        $retrieveRequest = $this->createRetrieveRequest('http://example.com', new Headers());

        $response = new RebuildableDecoratedResponse(
            new SuccessResponse($retrieveRequest->getHash())
        );

        $input = new ArrayInput([
            'response-json' => json_encode($response),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(SendResponseCommand::RETURN_CODE_RESOURCE_NOT_FOUND, $returnCode);
    }

    /**
     * @dataProvider runSuccessForFailureResponseDataProvider
     *
     * @param string $responseJson
     * @param array $expectedRequestData
     *
     * @throws \Exception
     */
    public function testRunSuccessForFailureResponseVerifyRequestData(
        string $responseJson,
        array $expectedRequestData
    ) {
        $this->httpMockHandler->appendFixtures([
            new Response(),
        ]);

        $callbackUrl = 'http://callback.example.com/';
        $retrieveRequest = $this->createRetrieveRequest('http://example.com/', new Headers(), [$callbackUrl]);
        $responseJson = str_replace('{{ requestHash }}', $retrieveRequest->getHash(), $responseJson);

        $expectedRequestData['request_id'] = str_replace(
            '{{ requestHash }}',
            $retrieveRequest->getHash(),
            $expectedRequestData['request_id']
        );

        $input = new ArrayInput([
            'response-json' => $responseJson,
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(SendResponseCommand::RETURN_CODE_OK, $returnCode);

        $lastRequest = $this->httpHistoryContainer->getLastRequest();

        $httpRequestAsserter = self::$container->get(HttpRequestAsserter::class);

        $httpRequestAsserter->assertSenderRequest(
            $lastRequest,
            $callbackUrl,
            $expectedRequestData
        );
    }

    public function runSuccessForFailureResponseDataProvider(): array
    {
        return [
            'unknown failure response, single callback URL' => [
                'responseJson' => json_encode(
                    new RebuildableDecoratedResponse(new UnknownFailureResponse('{{ requestHash }}'))
                ),
                'expectedRequestData' => [
                    'request_id' => '{{ requestHash }}',
                    'status' => 'failed',
                    'failure_type' => 'unknown',
                ],
            ],
            'http 404 failure response, single callback URL' => [
                'responseJson' => json_encode(
                    new RebuildableDecoratedResponse(new KnownFailureResponse(
                        '{{ requestHash }}',
                        KnownFailureResponse::TYPE_HTTP,
                        404
                    ))
                ),
                'expectedRequestData' => [
                    'request_id' => '{{ requestHash }}',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ],
            ],
            'curl 28 failure response, single callback URL' => [
                'responseJson' => json_encode(
                    new RebuildableDecoratedResponse(new KnownFailureResponse(
                        '{{ requestHash }}',
                        KnownFailureResponse::TYPE_CONNECTION,
                        28
                    ))
                ),
                'expectedRequestData' => [
                    'request_id' => '{{ requestHash }}',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                ],
            ],
            'unknown failure response, multiple callback URLs' => [
                'responseJson' => json_encode(
                    new RebuildableDecoratedResponse(new UnknownFailureResponse('{{ requestHash }}'))
                ),
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

        $callbackUrl = 'http://callback.example.com/';
        $retrieveRequest = $this->createRetrieveRequest('http://example.com/', new Headers(), [$callbackUrl]);
        $response = new RebuildableDecoratedResponse(new SuccessResponse($retrieveRequest->getHash()));

        $cachedResource = $this->createCachedResource(
            [
                'content-type' => 'text/plain',
            ],
            'resource content',
            $retrieveRequest
        );

        $expectedRequestData = [
            'request_id' => $retrieveRequest->getHash(),
            'status' => SuccessResponse::STATUS_SUCCESS,
            'headers' => $cachedResource->getHeaders()->toArray(),
            'content' => $cachedResource->getBody(),
        ];

        $input = new ArrayInput([
            'response-json' => json_encode($response),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(SendResponseCommand::RETURN_CODE_OK, $returnCode);

        $lastRequest = $this->httpHistoryContainer->getLastRequest();

        $httpRequestAsserter = self::$container->get(HttpRequestAsserter::class);

        $httpRequestAsserter->assertSenderRequest(
            $lastRequest,
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
            'http://callback3.example.com/',
        ];

        $this->httpMockHandler->appendFixtures(array_fill(0, 3, new Response()));

        $retrieveRequest = $this->createRetrieveRequest('http://example.com/', new Headers(), $callbackUrls);

        $response = new RebuildableDecoratedResponse(new UnknownFailureResponse($retrieveRequest->getHash()));

        $input = new ArrayInput([
            'response-json' => json_encode($response),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(SendResponseCommand::RETURN_CODE_OK, $returnCode);
        $this->assertEquals($callbackUrls, $this->httpHistoryContainer->getRequestUrlsAsStrings());
    }

    private function createRetrieveRequest(string $url, Headers $headers, array $callbackUrls = []): RetrieveRequest
    {
        $requestIdentifier = new RequestIdentifier($url, $headers);

        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl($url);
        $retrieveRequest->setHeaders($headers);
        $retrieveRequest->setHash($requestIdentifier);

        if (empty($callbackUrls)) {
            $callbackUrls = [
                'http://callback.example.com/',
            ];
        }

        foreach ($callbackUrls as $callbackUrl) {
            $retrieveRequest->addCallbackUrl($callbackUrl);
        }


        $this->retrieveRequestManager->persist($retrieveRequest);

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
}
