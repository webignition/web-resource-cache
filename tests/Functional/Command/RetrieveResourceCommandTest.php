<?php

namespace App\Tests\Functional\Command;

use App\Command\RetrieveResourceCommand;
use App\Entity\RetrieveRequest;
use App\Model\RequestIdentifier;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Resque\Job\RetrieveResourceJob;
use App\Resque\Job\SendResponseJob;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\ResqueQueueService;
use App\Services\RetrieveRequestManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Tests\Services\HttpMockHandler;
use App\Tests\UnhandledGuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use webignition\HttpHeaders\Headers;

class RetrieveResourceCommandTest extends AbstractFunctionalTestCase
{
    /**
     * @var RetrieveResourceCommand
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
     * @var RetrieveRequest
     */
    private $retrieveRequest;

    protected function setUp()
    {
        parent::setUp();

        $this->clearRedis();

        $this->command = self::$container->get(RetrieveResourceCommand::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->resqueQueueService = self::$container->get(ResqueQueueService::class);

        $retrieveRequestManager = self::$container->get(RetrieveRequestManager::class);

        $url = 'http://example.com/';
        $headers = new Headers();

        $requestIdentifier = new RequestIdentifier($url, $headers);

        $this->retrieveRequest = new RetrieveRequest();
        $this->retrieveRequest->setUrl($url);
        $this->retrieveRequest->setHeaders($headers);
        $this->retrieveRequest->setHash($requestIdentifier);
        $this->retrieveRequest->addCallbackUrl('http://callback.example.com/');

        $retrieveRequestManager->persist($this->retrieveRequest);
    }

    /**
     * @throws \Exception
     */
    public function testRunInvalidRequestHash()
    {
        $input = new ArrayInput([
            'request-hash' => 'invalid-request-hash',
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(RetrieveResourceCommand::RETURN_CODE_RETRIEVE_REQUEST_NOT_FOUND, $returnCode);
    }

    /**
     * @dataProvider runRetryingDataProvider
     *
     * @param array $httpFixtures
     *
     * @throws \Exception
     */
    public function testRunRetrying(array $httpFixtures)
    {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $this->assertTrue($this->resqueQueueService->isEmpty(RetrieveResourceJob::QUEUE_NAME));
        $this->assertTrue($this->resqueQueueService->isEmpty(SendResponseJob::QUEUE_NAME));

        $input = new ArrayInput([
            'request-hash' => $this->retrieveRequest->getHash(),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(RetrieveResourceCommand::RETURN_CODE_RETRYING, $returnCode);
        $this->assertTrue($this->resqueQueueService->isEmpty(SendResponseJob::QUEUE_NAME));
        $this->assertFalse($this->resqueQueueService->isEmpty(RetrieveResourceJob::QUEUE_NAME));
        $this->assertTrue($this->resqueQueueService->contains(RetrieveResourceJob::QUEUE_NAME, [
            'request-hash' => $this->retrieveRequest->getHash(),
        ]));
    }

    public function runRetryingDataProvider(): array
    {
        $http408Response = new Response(408);
        $http429Response = new Response(429);
        $http503Response = new Response(503);
        $http504Response = new Response(504);
        $curl6Exception = new ConnectException(
            'cURL error 6: foo',
            \Mockery::mock(RequestInterface::class)
        );
        $curl28Exception = new ConnectException(
            'cURL error 28: foo',
            \Mockery::mock(RequestInterface::class)
        );

        return [
            'HTTP 408' => [
                'httpFixtures' => [
                    $http408Response,
                ],
            ],
            'HTTP 429' => [
                'httpFixtures' => [
                    $http429Response,
                ],
            ],
            'HTTP 503' => [
                'httpFixtures' => array_fill(0, 6, $http503Response),
            ],
            'HTTP 504' => [
                'httpFixtures' => array_fill(0, 6, $http504Response),
            ],
            'curl 6' => [
                'httpFixtures' => array_fill(0, 6, $curl6Exception),
            ],
            'curl 28' => [
                'httpFixtures' => array_fill(0, 6, $curl28Exception),
            ],
        ];
    }

    /**
     * @dataProvider runSendResponseDataProvider
     *
     * @param array $httpFixtures
     * @param string $expectedResqueJobData
     * @throws \Exception
     */
    public function testRunSendResponse(array $httpFixtures, string $expectedResqueJobData)
    {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $this->assertTrue($this->resqueQueueService->isEmpty(RetrieveResourceJob::QUEUE_NAME));
        $this->assertTrue($this->resqueQueueService->isEmpty(SendResponseJob::QUEUE_NAME));

        $input = new ArrayInput([
            'request-hash' => $this->retrieveRequest->getHash(),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(RetrieveResourceCommand::RETURN_CODE_OK, $returnCode);
        $this->assertTrue($this->resqueQueueService->isEmpty(RetrieveResourceJob::QUEUE_NAME));
        $this->assertFalse($this->resqueQueueService->isEmpty(SendResponseJob::QUEUE_NAME));

        $expectedResqueJobData = str_replace(
            '{{ requestHash }}',
            $this->retrieveRequest->getHash(),
            $expectedResqueJobData
        );

        $this->assertTrue($this->resqueQueueService->contains(SendResponseJob::QUEUE_NAME, [
            'response-json' => $expectedResqueJobData,
        ]));
    }

    public function runSendResponseDataProvider(): array
    {
        $http301Response = new Response(301, ['location' => 'http://example.com/foo']);

        return [
            'Unknown failure' => [
                'httpFixtures' => [
                    new UnhandledGuzzleException(),
                ],
                'expectedResqueJobData' => json_encode([
                    'request_id' => '{{ requestHash }}',
                    'status' => UnknownFailureResponse::STATUS_FAILED,
                    'failure_type' => UnknownFailureResponse::TYPE_UNKNOWN,
                    'class' => UnknownFailureResponse::class,
                ]),
            ],
            'HTTP 200' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'expectedResqueJobData' => json_encode([
                    'request_id' => '{{ requestHash }}',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                    'class' => SuccessResponse::class,
                ]),
            ],
            'HTTP 404' => [
                'httpFixtures' => [
                    new Response(404),
                ],
                'expectedResqueJobData' => json_encode([
                    'request_id' => '{{ requestHash }}',
                    'status' => KnownFailureResponse::STATUS_FAILED,
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                    'class' => KnownFailureResponse::class,
                ]),
            ],
            'HTTP 301' => [
                'httpFixtures' => array_fill(0, 6, $http301Response),
                'expectedResqueJobData' => json_encode([
                    'request_id' => '{{ requestHash }}',
                    'status' => KnownFailureResponse::STATUS_FAILED,
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 301,
                    'class' => KnownFailureResponse::class,
                ]),
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function testRunUpdatesResource()
    {
        $cachedResourceFactory = self::$container->get(CachedResourceFactory::class);
        $cachedResourceManager = self::$container->get(CachedResourceManager::class);

        $currentHeaders = [
            'foo' => [
                'bar',
            ],
        ];

        $currentBody = 'current body content';

        $cachedResource = $cachedResourceFactory->create(
            $this->retrieveRequest,
            new Response(200, $currentHeaders, $currentBody)
        );

        $cachedResourceManager->update($cachedResource);

        $this->assertSame($currentHeaders, $cachedResource->getHeaders()->toArray());
        $this->assertSame($currentBody, $cachedResource->getBody());

        $updatedHeaders = [
            'fizz' => [
                'buzz',
            ],
        ];

        $updatedBody = 'updated body content';

        $this->httpMockHandler->appendFixtures([
            new Response(200, $updatedHeaders, $updatedBody),
        ]);

        $input = new ArrayInput([
            'request-hash' => $this->retrieveRequest->getHash(),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(RetrieveResourceCommand::RETURN_CODE_OK, $returnCode);

        $cachedResourceHeaders = $cachedResource->getHeaders()->toArray();

        $this->assertArrayNotHasKey('foo', $cachedResourceHeaders);
        $this->assertSame($updatedHeaders['fizz'], $cachedResourceHeaders['fizz']);
        $this->assertSame($updatedBody, $cachedResource->getBody());
    }
}
