<?php

namespace App\Tests\Functional\Services;

use App\Entity\RetrieveRequest;
use App\Exception\TransportException;
use App\Services\ResourceRetriever;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Tests\Services\HttpMockHandler;
use App\Tests\UnhandledGuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
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
     * @throws \App\Exception\TransportException
     */
    public function testRetrieveReturnsResponse(array $httpFixtures, int $expectedResponseStatusCode)
    {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl('http://example.com/');
        $retrieveRequest->addCallbackUrl('http://callback.example.com/');

        $requestResponse = $this->resourceRetriever->retrieve($retrieveRequest);
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
     * @throws TransportException
     */
    public function testReturnedRequestUsesRedirectUrl()
    {
        $http200Response = new Response(200);
        $http301Response = new Response(301, ['location' => 'http://example.com/foo']);

        $this->httpMockHandler->appendFixtures([
            $http301Response,
            $http200Response,
        ]);

        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl('http://example.com/');
        $retrieveRequest->addCallbackUrl('http://callback.example.com/');

        $requestResponse = $this->resourceRetriever->retrieve($retrieveRequest);
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

        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl('http://example.com/');
        $retrieveRequest->addCallbackUrl('http://callback.example.com/');

        try {
            $this->resourceRetriever->retrieve($retrieveRequest);
            $this->fail('TransportException not thrown');
        } catch (TransportException $transportException) {
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
