<?php

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Entity\Callback;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\DecoratedSuccessResponse;
use App\Model\Response\ResponseInterface;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Services\CallbackResponseLogger;
use App\Services\ResponseSender;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Tests\Services\Asserter\HttpRequestAsserter;
use App\Tests\Services\HttpMockHandler;
use Grpc\Call;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use webignition\HttpHeaders\Headers;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class ResponseSenderTest extends AbstractFunctionalTestCase
{
    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    /**
     * @var ResponseSender
     */
    private $responseSender;

    protected function setUp()
    {
        parent::setUp();

        $this->responseSender = self::$container->get(ResponseSender::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    /**
     * @dataProvider sendFailureDataProvider
     *
     * @param array $httpFixtures
     * @param string $requestHash
     * @param array $expectedLogContext
     */
    public function testSendFailure(array $httpFixtures, string $requestHash, array $expectedLogContext)
    {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->withArgs(function (string $message, array $context) use ($requestHash, $expectedLogContext) {
                $this->assertEquals('Callback failed', $message);

                $this->assertEquals($expectedLogContext, $context);

                return true;
            });

        $url = 'http://example.com/';
        $callback = new Callback();
        $callback->setUrl($url);
        $callback->setRequestHash($requestHash);

        $response = \Mockery::mock(ResponseInterface::class);

        $response
            ->shouldReceive('getRequestId')
            ->andReturn($requestHash);

        $response
            ->shouldReceive('jsonSerialize')
            ->andReturn('');

        $this->setObjectPrivateProperty(
            $this->responseSender,
            ResponseSender::class,
            'logger',
            $logger
        );

        $this->assertFalse($this->responseSender->send($callback, $response));
    }

    public function sendFailureDataProvider(): array
    {
        return [
            'curl 28' => [
                'httpFixtures' => [
                    new ConnectException(
                        'cURL error 28: foo',
                        \Mockery::mock(RequestInterface::class)
                    ),
                ],
                'requestHash' => 'request_hash_1',
                'expectedLogContext' => [
                    'requestId' => 'request_hash_1',
                    'code' => 0,
                    'message' => 'cURL error 28: foo',
                ],
            ],
            'HTTP 404' => [
                'httpFixtures' => [
                    new Response(404),
                ],
                'requestHash' => 'request_hash_2',
                'expectedLogContext' => [
                    'requestId' => 'request_hash_2',
                    'code' => 404,
                    'message' => 'Client error: `POST http://example.com/` resulted in a `404 Not Found` response',
                ],
            ],
        ];
    }

    /**
     * @dataProvider sendSuccessDataProvider
     *
     * @param ResponseInterface $response
     * @param array $expectedRequestData
     */
    public function testSendSuccessNoLogResponse(ResponseInterface $response, array $expectedRequestData)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(),
        ]);

        $url = 'http://example.com/';

        $callback = new Callback();
        $callback->setRequestHash('request_hash');
        $callback->setUrl($url);

        $this->assertTrue($this->responseSender->send($callback, $response));
        $this->assertEquals($url, $this->httpHistoryContainer->getLastRequestUrl());

        $lastRequest = $this->httpHistoryContainer->getLastRequest();

        $httpRequestAsserter = self::$container->get(HttpRequestAsserter::class);

        $httpRequestAsserter->assertSenderRequest(
            $lastRequest,
            $url,
            $expectedRequestData
        );
    }

    public function sendSuccessDataProvider()
    {
        return [
            'unknown failure response' => [
                'response' => new UnknownFailureResponse('request_hash'),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => 'unknown',
                ],
            ],
            'http 404 failure response' => [
                'response' => new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_HTTP, 404),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                    'context' => [],
                ],
            ],
            'http 500 failure response' => [
                'response' => new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_HTTP, 500),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 500,
                    'context' => [],
                ],
            ],
            'curl 6 failure response' => [
                'response' => new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_CONNECTION, 6),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 6,
                    'context' => [],
                ],
            ],
            'curl 28 failure response' => [
                'response' => new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_CONNECTION, 28),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                    'context' => [],
                ],
            ],
            'success response, text/plain, no additional headers' => [
                'response' => new DecoratedSuccessResponse(
                    new SuccessResponse('request_hash'),
                    $this->createCachedResource(['content-type' => 'text/plain'], 'response content')
                ),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'success',
                    'headers' => [
                        'content-type' => [
                            'text/plain',
                        ],
                    ],
                    'content' => base64_encode('response content'),
                ],
            ],
            'success response, text/plain, has additional headers' => [
                'response' => new DecoratedSuccessResponse(
                    new SuccessResponse('request_hash'),
                    $this->createCachedResource(
                        [
                            'content-type' => 'text/plain',
                            'foo' => 'bar',
                        ],
                        'response content'
                    )
                ),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'success',
                    'headers' => [
                        'content-type' => [
                            'text/plain',
                        ],
                        'foo' => [
                            'bar',
                        ],
                    ],
                    'content' => base64_encode('response content'),
                ],
            ],
            'success response, text/html, no additional headers' => [
                'response' => new DecoratedSuccessResponse(
                    new SuccessResponse('request_hash'),
                    $this->createCachedResource(['content-type' => 'text/html'], '<!doctype><html></html>')
                ),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'success',
                    'headers' => [
                        'content-type' => [
                            'text/html',
                        ],
                    ],
                    'content' => base64_encode('<!doctype><html></html>'),
                ],
            ],
        ];
    }

    public function testSendSuccessLogResponse()
    {
        $callbackHttpResponse = new Response();

        $this->httpMockHandler->appendFixtures([
            $callbackHttpResponse,
        ]);

        $url = 'http://example.com/';
        $requestHash = 'request_hash';

        $callback = new Callback();
        $callback->setRequestHash($requestHash);
        $callback->setUrl($url);
        $callback->setLogResponse(true);

        $callbackResponseLogger = \Mockery::mock(CallbackResponseLogger::class);
        $callbackResponseLogger
            ->shouldReceive('log')
            ->with($requestHash, $callbackHttpResponse);

        $responseSender = new ResponseSender(
            self::$container->get('async_http_retriever.http.client.sender'),
            self::$container->get(LoggerInterface::class),
            $callbackResponseLogger
        );

        $responseSenderReturnValue = $responseSender->send($callback, new UnknownFailureResponse($requestHash));

        $this->assertTrue($responseSenderReturnValue);
    }

    private function createCachedResource(array $headers, string $content): CachedResource
    {
        $cachedResource = new CachedResource();
        $cachedResource->setHeaders(new Headers($headers));
        $cachedResource->setBody($content);

        return $cachedResource;
    }

    private function setObjectPrivateProperty(
        $object,
        $objectClass,
        $propertyName,
        $propertyValue
    ) {
        try {
            $reflector = new \ReflectionClass($objectClass);
            $property = $reflector->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($object, $propertyValue);
        } catch (\ReflectionException $exception) {
        }
    }
}
