<?php

namespace App\Tests\Functional\MessageHandler;

use App\Message\RetrieveResource;
use App\Message\SendResponse;
use App\MessageHandler\RetrieveResourceHandler;
use App\Model\RequestIdentifier;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\CallbackFactory;
use App\Services\CallbackManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Tests\Services\Asserter\HttpRequestAsserter;
use App\Tests\Services\HttpMockHandler;
use App\Tests\UnhandledGuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\HttpHeaders\Headers;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class RetrieveResourceHandlerTest extends AbstractFunctionalTestCase
{
    /**
     * @var RetrieveResourceHandler
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

    /**
     * @var RetrieveResource
     */
    private $retrieveResourceMessage;

    protected function setUp()
    {
        parent::setUp();

        $this->handler = self::$container->get(RetrieveResourceHandler::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->cachedResourceFactory = self::$container->get(CachedResourceFactory::class);
        $this->cachedResourceManager = self::$container->get(CachedResourceManager::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
        $this->callbackFactory = self::$container->get(CallbackFactory::class);
        $this->callbackManager = self::$container->get(CallbackManager::class);
        $this->httpRequestAsserter = self::$container->get(HttpRequestAsserter::class);

        $url = 'http://example.com/';
        $requestIdentifier = new RequestIdentifier($url, []);

        $this->retrieveResourceMessage = new RetrieveResource($requestIdentifier->getHash(), $url, new Headers(), []);
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

        $messageBus = \Mockery::spy(MessageBusInterface::class);
        $this->setHandlerMessageBus($this->handler, $messageBus);

        $this->assertEquals(0, $this->retrieveResourceMessage->getRetryCount());

        $this->handler->__invoke($this->retrieveResourceMessage);

        $expectedUpdatedRetrieveResourceMessage = clone $this->retrieveResourceMessage;
        $expectedUpdatedRetrieveResourceMessage->incrementRetryCount();

        $messageBus
            ->shouldHaveReceived('dispatch')
            ->withArgs(function (RetrieveResource $retrieveResourceMessage) {

                $this->assertSame($this->retrieveResourceMessage, $retrieveResourceMessage);
                $this->assertSame(1, $retrieveResourceMessage->getRetryCount());

                return true;
            });
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
     * @param RetrieveResource $retrieveResourceMessage
     * @param array $expectedResponseData
     */
    public function testRunSendResponse(
        array $httpFixtures,
        RetrieveResource $retrieveResourceMessage,
        array $expectedResponseData
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $messageBus = \Mockery::spy(MessageBusInterface::class);
        $this->setHandlerMessageBus($this->handler, $messageBus);

        $this->handler->__invoke($retrieveResourceMessage);

        $messageBus
            ->shouldHaveReceived('dispatch')
            ->withArgs(function (SendResponse $sendResponseMessage) use ($expectedResponseData) {
                $this->assertEquals($expectedResponseData, $sendResponseMessage->getResponseData());

                return true;
            });
    }

    public function runSendResponseDataProvider(): array
    {
        $retrieveResourceMessage = new RetrieveResource(
            'request_hash',
            'http://example.com/',
            new Headers(),
            []
        );

        $http301Response = new Response(301, ['location' => 'http://example.com/redirect']);

        return [
            'Unknown failure' => [
                'httpFixtures' => [
                    new UnhandledGuzzleException(),
                ],
                'retrieveResourceMessage' => $retrieveResourceMessage,
                'expectedResponseData' => (new UnknownFailureResponse('request_hash'))->jsonSerialize(),
            ],
            'HTTP 200' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'retrieveResourceMessage' => $retrieveResourceMessage,
                'expectedResponseData' => (new SuccessResponse('request_hash'))->jsonSerialize(),
            ],
            'HTTP 404' => [
                'httpFixtures' => [
                    new Response(404),
                ],
                'retrieveResourceMessage' => $retrieveResourceMessage,
                'expectedResponseData' => (new KnownFailureResponse(
                    'request_hash',
                    KnownFailureResponse::TYPE_HTTP,
                    404
                ))->jsonSerialize(),
            ],
            'HTTP 301 (redirect loop)' => [
                'httpFixtures' => array_fill(0, 6, $http301Response),
                'retrieveResourceMessage' => $retrieveResourceMessage,
                'expectedResponseData' => (new KnownFailureResponse(
                    'request_hash',
                    KnownFailureResponse::TYPE_HTTP,
                    301,
                    [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => true,
                        'history' => [
                            'http://example.com/',
                            'http://example.com/redirect',
                            'http://example.com/redirect',
                            'http://example.com/redirect',
                            'http://example.com/redirect',
                            'http://example.com/redirect',
                        ],
                    ]
                ))->jsonSerialize(),
            ],
            'HTTP 301 (not redirect loop)' => [
                'httpFixtures' => [
                    new Response(301, ['location' => 'http://example.com/1']),
                    new Response(301, ['location' => 'http://example.com/2']),
                    new Response(301, ['location' => 'http://example.com/3']),
                    new Response(301, ['location' => 'http://example.com/4']),
                    new Response(301, ['location' => 'http://example.com/5']),
                    new Response(301, ['location' => 'http://example.com/6']),
                ],
                'retrieveResourceMessage' => $retrieveResourceMessage,
                'expectedResponseData' => (new KnownFailureResponse(
                    'request_hash',
                    KnownFailureResponse::TYPE_HTTP,
                    301,
                    [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => false,
                        'history' => [
                            'http://example.com/',
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                            'http://example.com/4',
                            'http://example.com/5',
                        ],
                    ]
                ))->jsonSerialize(),
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
            $this->retrieveResourceMessage->getRequestHash(),
            $this->retrieveResourceMessage->getUrl(),
            new Response(200, $currentHeaders, $currentBody)
        );

        $cachedResourceManager->update($cachedResource);

        $this->assertSame($currentHeaders, $cachedResource->getHeaders()->toArray());
        $this->assertSame($currentBody, stream_get_contents($cachedResource->getBody()));

        $updatedHeaders = [
            'fizz' => [
                'buzz',
            ],
        ];

        $updatedBody = 'updated body content';

        $this->httpMockHandler->appendFixtures([
            new Response(200, $updatedHeaders, $updatedBody),
        ]);

        $messageBus = \Mockery::spy(MessageBusInterface::class);
        $this->setHandlerMessageBus($this->handler, $messageBus);

        $this->handler->__invoke($this->retrieveResourceMessage);

        $cachedResourceHeaders = $cachedResource->getHeaders()->toArray();

        $this->assertArrayNotHasKey('foo', $cachedResourceHeaders);
        $this->assertSame($updatedHeaders['fizz'], $cachedResourceHeaders['fizz']);
        $this->assertSame($updatedBody, stream_get_contents($cachedResource->getBody()));
    }

    private function setHandlerMessageBus(RetrieveResourceHandler $handler, MessageBusInterface $messageBus)
    {
        try {
            $reflector = new \ReflectionClass(RetrieveResourceHandler::class);
            $property = $reflector->getProperty('messageBus');
            $property->setAccessible(true);
            $property->setValue($handler, $messageBus);
        } catch (\ReflectionException $exception) {
        }
    }
}
