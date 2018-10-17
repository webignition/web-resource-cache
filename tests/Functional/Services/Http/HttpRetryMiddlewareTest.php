<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\HttpRetryMiddleware;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Tests\Services\HttpMockHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class HttpRetryMiddlewareTest extends AbstractFunctionalTestCase
{
    const REQUEST_URL = 'http://example.com/';
    const RESPONSE_BODY = 'response body content';

    /**
     * @var HttpRetryMiddleware
     */
    private $httpRetryMiddleware;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @var Client
     */
    private $httpClient;

    protected function setUp()
    {
        parent::setUp();

        $this->httpRetryMiddleware = self::$container->get(HttpRetryMiddleware::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->handlerStack = self::$container->get(HandlerStack::class);

        $this->httpClient = new Client([
            'handler' => $this->handlerStack,
        ]);

        $this->httpMockHandler->appendFixtures([
            new Response(500),
            new Response(200, [], self::RESPONSE_BODY),
        ]);
    }

    public function testWithoutEnabling()
    {
        $this->expectException(ServerException::class);

        $this->httpClient->get(self::REQUEST_URL);
    }

    public function testAfterEnabling()
    {
        $this->httpRetryMiddleware->enable();

        $response = $this->httpClient->get(self::REQUEST_URL);

        $this->assertEquals(self::RESPONSE_BODY, $response->getBody()->getContents());
    }

    public function testAfterDisabling()
    {
        $this->httpRetryMiddleware->enable();
        $this->httpRetryMiddleware->disable();

        $this->expectException(ServerException::class);

        $this->httpClient->get(self::REQUEST_URL);
    }
}
