<?php

namespace App\Tests\Functional\Services;

use App\Exception\HttpTransportException;
use App\Model\RequestParameters;
use App\Services\ResourceRetriever;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Tests\Services\HttpMockHandler;
use App\Tests\UnhandledGuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use webignition\HttpHeaders\Headers;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class ResourceRetrieverTest extends AbstractFunctionalTestCase
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
     * @var ResourceRetriever
     */
    private $resourceRetriever;

    protected function setUp()
    {
        parent::setUp();

        $this->resourceRetriever = self::$container->get(ResourceRetriever::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    /**
     * @dataProvider retrieveReturnsResponseDataProvider
     *
     * @param array $httpFixtures
     * @param int $expectedResponseStatusCode
     *
     * @throws \App\Exception\HttpTransportException
     */
    public function testRetrieveReturnsResponse(array $httpFixtures, int $expectedResponseStatusCode)
    {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $requestResponse = $this->resourceRetriever->retrieve(
            'http://example.com/',
            new Headers(),
            new RequestParameters()
        );

        $response = $requestResponse->getResponse();
        $this->assertSame($expectedResponseStatusCode, $response->getStatusCode());
    }

    public function retrieveReturnsResponseDataProvider(): array
    {
        $http200Response = new Response(200);
        $http301Response = new Response(301, ['location' => 'http://example.com/foo']);
        $http404Response = new Response(404);
        $http500Response = new Response(500);
        $curl28Exception = new ConnectException(
            'cURL error 28: foo',
            \Mockery::mock(RequestInterface::class)
        );

        return [
            '200 OK only' => [
                'httpFixtures' => [
                    $http200Response,
                ],
                'expectedResponseStatusCode' => 200,
            ],
            '404 Not Found only' => [
                'httpFixtures' => [
                    $http404Response,
                ],
                'expectedResponseStatusCode' => 404,
            ],
            '500 Internal Server Error only' => [
                'httpFixtures' => array_fill(0, 6, $http500Response),
                'expectedResponseStatusCode' => 500,
            ],
            '301 then 200' => [
                'httpFixtures' => [
                    $http301Response,
                    $http200Response,
                ],
                'expectedResponseStatusCode' => 200,
            ],
            'many 301 then 200' => [
                'httpFixtures' => [
                    $http301Response,
                    $http301Response,
                    $http301Response,
                    $http301Response,
                    $http301Response,
                    $http200Response,
                ],
                'expectedResponseStatusCode' => 200,
            ],
            '500 then 200' => [
                'httpFixtures' => [
                    $http500Response,
                    $http200Response,
                ],
                'expectedResponseStatusCode' => 200,
            ],
            'curl 28 then 200' => [
                'httpFixtures' => [
                    $curl28Exception,
                    $http200Response,
                ],
                'expectedResponseStatusCode' => 200,
            ],
            'many curl 28 then 200' => [
                'httpFixtures' => [
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $http200Response,
                ],
                'expectedResponseStatusCode' => 200,
            ],
        ];
    }

    /**
     * @dataProvider retrieveRequestHeadersDataProvider
     *
     * @param array $httpFixtures
     * @param string $url
     * @param Headers $headers
     * @param RequestParameters $requestParameters
     * @param array $expectedRequestHeaderCollection
     *
     * @throws HttpTransportException
     */
    public function testRetrieveRequestHeaders(
        array $httpFixtures,
        string $url,
        Headers $headers,
        RequestParameters $requestParameters,
        array $expectedRequestHeaderCollection
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $requestResponse = $this->resourceRetriever->retrieve($url, $headers, $requestParameters);
        $response = $requestResponse->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount($this->httpHistoryContainer->count(), $expectedRequestHeaderCollection);

        foreach ($this->httpHistoryContainer->getRequests() as $requestIndex => $request) {
            $expectedRequestHeaders = $expectedRequestHeaderCollection[$requestIndex];
            $this->assertEquals($expectedRequestHeaders, $request->getHeaders());
        }
    }

    public function retrieveRequestHeadersDataProvider(): array
    {
        return [
            'single request, no explicit headers, no parameters' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'url' => 'http://example.com',
                'headers' => new Headers(),
                'parameters' => new RequestParameters(),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'example.com',
                        ],
                    ],
                ],
            ],
            'single request, basic http authorization headers, no parameters' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'url' => 'http://example.com',
                'headers' => new Headers([
                    'Authorization' => 'Basic ' . base64_encode('example:password'),
                ]),
                'parameters' => new RequestParameters(),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'example.com',
                        ],
                        'authorization' => [
                            'Basic ' . base64_encode('example:password'),
                        ],
                    ],
                ],
            ],
            'single redirect on same host, no explicit headers, no parameters' => [
                'httpFixtures' => [
                    new Response(301, [
                        'location' => 'http://example.com/foo'
                    ]),
                    new Response(200),
                ],
                'url' => 'http://example.com',
                'headers' => new Headers(),
                'parameters' => new RequestParameters(),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'example.com',
                        ],
                    ],
                    [
                        'User-Agent' => [
                            'GuzzleHttp/6.3.3 curl/7.52.1 PHP/7.2.11',
                        ],
                        'Host' => [
                            'example.com',
                        ],
                    ],
                ],
            ],
            'single redirect on different host, no explicit headers, no parameters' => [
                'httpFixtures' => [
                    new Response(301, [
                        'location' => 'http://foo.example.com'
                    ]),
                    new Response(200),
                ],
                'url' => 'http://example.com',
                'headers' => new Headers(),
                'parameters' => new RequestParameters(),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'example.com',
                        ],
                    ],
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'foo.example.com',
                        ],
                    ],
                ],
            ],
            'single request, cookie header, no parameters' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'url' => 'http://example.com',
                'headers' => new Headers([
                    'cookie' => 'foo1=value1; foo2=value2'
                ]),
                'parameters' => new RequestParameters(),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'example.com',
                        ],
                    ],
                ],
            ],
            'single request, cookie header, non-matching cookie parameters (no match on domain)' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'url' => 'http://example.com',
                'headers' => new Headers([
                    'cookie' => 'foo1=value1; foo2=value2'
                ]),
                'parameters' => new RequestParameters([
                    'cookies' => [
                        'domain' => 'foo.example.com',
                        'path' => '/',
                    ],
                ]),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'example.com',
                        ],
                    ],
                ],
            ],
            'single request, cookie header, non-matching cookie parameters (no match on path)' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'url' => 'http://example.com',
                'headers' => new Headers([
                    'cookie' => 'foo1=value1; foo2=value2'
                ]),
                'parameters' => new RequestParameters([
                    'cookies' => [
                        'domain' => '.example.com',
                        'path' => '/foo',
                    ],
                ]),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'example.com',
                        ],
                    ],
                ],
            ],
            'single request, cookie header, matching cookie parameters (exact domain, minimal path)' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'url' => 'http://example.com',
                'headers' => new Headers([
                    'cookie' => 'foo1=value1; foo2=value2'
                ]),
                'parameters' => new RequestParameters([
                    'cookies' => [
                        'domain' => 'example.com',
                        'path' => '/',
                    ],
                ]),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'example.com',
                        ],
                        'Cookie' => [
                            'foo1=value1; foo2=value2',
                        ],
                    ],
                ],
            ],
            'single request, cookie header, matching cookie parameters (exact domain, non-minimal path)' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'url' => 'http://example.com/foo',
                'headers' => new Headers([
                    'cookie' => 'foo1=value1; foo2=value2'
                ]),
                'parameters' => new RequestParameters([
                    'cookies' => [
                        'domain' => 'example.com',
                        'path' => '/foo',
                    ],
                ]),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'example.com',
                        ],
                        'Cookie' => [
                            'foo1=value1; foo2=value2',
                        ],
                    ],
                ],
            ],
            'single request, cookie header, matching cookie parameters (wildcard domain, minimal path)' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'url' => 'http://foo.example.com',
                'headers' => new Headers([
                    'cookie' => 'foo1=value1; foo2=value2'
                ]),
                'parameters' => new RequestParameters([
                    'cookies' => [
                        'domain' => '.example.com',
                        'path' => '/',
                    ],
                ]),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'foo.example.com',
                        ],
                        'Cookie' => [
                            'foo1=value1; foo2=value2',
                        ],
                    ],
                ],
            ],
            'single redirect on different host, cookie header matches first request only' => [
                'httpFixtures' => [
                    new Response(301, [
                        'location' => 'http://anotherexample.com'
                    ]),
                    new Response(200),
                ],
                'url' => 'http://example.com',
                'headers' => new Headers([
                    'cookie' => 'foo1=value1; foo2=value2'
                ]),
                'parameters' => new RequestParameters([
                    'cookies' => [
                        'domain' => '.example.com',
                        'path' => '/',
                    ],
                ]),
                'expectedRequestHeadersCollection' => [
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'example.com',
                        ],
                        'Cookie' => [
                            'foo1=value1; foo2=value2',
                        ],
                    ],
                    [
                        'User-Agent' => [
                            \GuzzleHttp\default_user_agent(),
                        ],
                        'Host' => [
                            'anotherexample.com',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws HttpTransportException
     */
    public function testReturnedRequestUsesRedirectUrl()
    {
        $http200Response = new Response(200);
        $http301Response = new Response(301, ['location' => 'http://example.com/foo']);

        $this->httpMockHandler->appendFixtures([
            $http301Response,
            $http200Response,
        ]);

        $requestResponse = $this->resourceRetriever->retrieve(
            'http://example.com/',
            new Headers(),
            new RequestParameters()
        );
        $request = $requestResponse->getRequest();

        $this->assertEquals('http://example.com/foo', $request->getUri());
    }

    /**
     * @dataProvider retrieveThrowsTransportExceptionDataProvider
     *
     * @param array $httpFixtures
     *
     * @param int $expectedTransportErrorCode
     * @param bool $expectedIsCurlException
     * @param bool $expectedIsTooManyRedirectsException
     */
    public function testRetrieveThrowsTransportException(
        array $httpFixtures,
        int $expectedTransportErrorCode,
        bool $expectedIsCurlException,
        bool $expectedIsTooManyRedirectsException
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        try {
            $this->resourceRetriever->retrieve('http://example.com/', new Headers(), new RequestParameters());
            $this->fail('HttpTransportException not thrown');
        } catch (HttpTransportException $transportException) {
            $this->assertSame($expectedTransportErrorCode, $transportException->getTransportErrorCode());
            $this->assertSame($expectedIsCurlException, $transportException->isCurlException());
            $this->assertSame($expectedIsTooManyRedirectsException, $transportException->isTooManyRedirectsException());
            $this->assertInstanceOf(RequestInterface::class, $transportException->getRequest());
        }
    }

    public function retrieveThrowsTransportExceptionDataProvider(): array
    {
        $curl28Exception = new ConnectException(
            'cURL error 28: foo',
            \Mockery::mock(RequestInterface::class)
        );

        $http301Response = new Response(301, ['location' => 'http://example.com/']);
        $unhandledGuzzleException = new UnhandledGuzzleException();

        return [
            'too many redirects' => [
                'httpFixtures' => [
                    $http301Response,
                    $http301Response,
                    $http301Response,
                    $http301Response,
                    $http301Response,
                    $http301Response,
                ],
                'expectedTransportErrorCode' => 0,
                'expectedIsCurlException' => false,
                'expectedIsTooManyRedirectsException' => true,
            ],
            'curl 28' => [
                'httpFixtures' => [
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                ],
                'expectedTransportErrorCode' => 28,
                'expectedIsCurlException' => true,
                'expectedIsTooManyRedirectsException' => false,
            ],
            'unknown guzzle exception' => [
                'httpFixtures' => [
                    $unhandledGuzzleException,
                ],
                'expectedTransportErrorCode' => 0,
                'expectedIsCurlException' => false,
                'expectedIsTooManyRedirectsException' => false,
            ],
        ];
    }
}
