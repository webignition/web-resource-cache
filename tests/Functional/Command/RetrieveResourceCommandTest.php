<?php

namespace App\Tests\Functional\Command;

use App\Command\RetrieveResourceCommand;
use App\Model\RequestIdentifier;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Model\RetrieveRequest;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
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
     * @var RetrieveRequest
     */
    private $retrieveRequest;

    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(RetrieveResourceCommand::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);

        $url = 'http://example.com/';
        $requestIdentifier = new RequestIdentifier($url, new Headers());

        $this->retrieveRequest = new RetrieveRequest($requestIdentifier->getHash(), $url);
    }

    /**
     * @throws \Exception
     */
    public function testRunInvalidRequestHash()
    {
        $input = new ArrayInput([
            'request-json' => 'invalid-request-hash',
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

        // Fix in #169
        // Assert that 'retrieve resource' message bus is empty
        // Assert that 'send response' message bus is empty

        $input = new ArrayInput([
            'request-json' => json_encode($this->retrieveRequest),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(RetrieveResourceCommand::RETURN_CODE_RETRYING, $returnCode);

        // Fix in #169
        // Assert that 'retrieve resource' message bus is empty
        // Assert that 'send response' message bus is not empty

        $expectedUpdatedRetrieveRequest = clone $this->retrieveRequest;
        $expectedUpdatedRetrieveRequest->incrementRetryCount();

        // Fix in #169
        // Assert that 'send response' message bus contains expected message
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

        // Fix in #169
        // Assert that 'retrieve resource' message bus is empty
        // Assert that 'send response' message bus is empty

        $input = new ArrayInput([
            'request-json' => json_encode($this->retrieveRequest),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(RetrieveResourceCommand::RETURN_CODE_OK, $returnCode);

        // Fix in #169
        // Assert that 'retrieve resource' message bus is empty
        // Assert that 'send response' message bus is not empty

        $expectedResqueJobData = str_replace(
            '{{ requestHash }}',
            $this->retrieveRequest->getRequestHash(),
            $expectedResqueJobData
        );

        // Fix in #169
        // Assert that 'send response' message bus contains expected message
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
            $this->retrieveRequest->getRequestHash(),
            $this->retrieveRequest->getUrl(),
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
            'request-json' => json_encode($this->retrieveRequest),
        ]);

        $returnCode = $this->command->run($input, new NullOutput());

        $this->assertEquals(RetrieveResourceCommand::RETURN_CODE_OK, $returnCode);

        $cachedResourceHeaders = $cachedResource->getHeaders()->toArray();

        $this->assertArrayNotHasKey('foo', $cachedResourceHeaders);
        $this->assertSame($updatedHeaders['fizz'], $cachedResourceHeaders['fizz']);
        $this->assertSame($updatedBody, $cachedResource->getBody());
    }
}
