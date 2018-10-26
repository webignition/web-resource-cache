<?php

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\PresentationDecoratedSuccessResponse;
use App\Model\Response\ResponseInterface;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Services\ResponseSender;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Tests\Services\Asserter\HttpRequestAsserter;
use App\Tests\Services\HttpMockHandler;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
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
     */
    public function testSendFailure(array $httpFixtures)
    {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $url = 'http://example.com/';
        $response = \Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('jsonSerialize')
            ->andReturn('');

        $this->assertFalse($this->responseSender->send($url, $response));
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
            ],
            'HTTP 404' => [
                'httpFixtures' => [
                    new Response(404),
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
    public function testSendSuccess(ResponseInterface $response, array $expectedRequestData)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(),
        ]);

        $url = 'http://example.com/';

        $this->assertTrue($this->responseSender->send($url, $response));
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
                ],
            ],
            'http 500 failure response' => [
                'response' => new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_HTTP, 500),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 500,
                ],
            ],
            'curl 6 failure response' => [
                'response' => new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_CONNECTION, 6),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 6,
                ],
            ],
            'curl 28 failure response' => [
                'response' => new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_CONNECTION, 28),
                'expectedRequestData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                ],
            ],
            'success response, text/plain, no additional headers' => [
                'response' => new PresentationDecoratedSuccessResponse(
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
                    'content' => 'response content',
                ],
            ],
            'success response, text/plain, has additional headers' => [
                'response' => new PresentationDecoratedSuccessResponse(
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
                    'content' => 'response content',
                ],
            ],
            'success response, text/html, no additional headers' => [
                'response' => new PresentationDecoratedSuccessResponse(
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
                    'content' => '<!doctype><html></html>',
                ],
            ],
        ];
    }

    private function createCachedResource(array $headers, string $content): CachedResource
    {
        $cachedResource = new CachedResource();
        $cachedResource->setHeaders(new Headers($headers));
        $cachedResource->setBody($content);

        return $cachedResource;
    }
}
