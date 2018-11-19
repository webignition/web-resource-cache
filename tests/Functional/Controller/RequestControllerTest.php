<?php

namespace App\Tests\Functional\Controller;

use App\Controller\RequestController;
use App\Entity\CachedResource;
use App\Entity\Callback as CallbackEntity;
use App\Message\RetrieveResource;
use App\Message\SendResponse;
use App\Model\RequestIdentifier;
use App\Services\CallbackManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use webignition\HttpHeaders\Headers;

class RequestControllerTest extends AbstractFunctionalTestCase
{
    const ROUTE_NAME = 'get';

    const URL_1_EXAMPLE_COM = 'http://1.example.com/';
    const URL_2_EXAMPLE_COM = 'http://2.example.com/';

    /**
     * @var string
     */
    private $routeUrl;

    /**
     * @var int
     */
    private $messageIndex;

    protected function setUp()
    {
        parent::setUp();

        /* @var RouterInterface $router */
        $router = self::$container->get(RouterInterface::class);

        $this->routeUrl = $router->generate('get');
    }

    public function testGetRequest()
    {
        $this->client->request('GET', $this->routeUrl);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    }

    public function testPostRequest()
    {
        $this->client->request('POST', $this->routeUrl);
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @dataProvider successfulRequestsFromEmptyDataProvider
     *
     * @param array $requestDataCollection
     * @param array $expectedResponseDataCollection
     * @param CallbackEntity[] $expectedCallbacks
     * @param array $expectedRetrieveResourceMessages
     */
    public function testSuccessfulRequestsFromEmpty(
        array $requestDataCollection,
        array $expectedResponseDataCollection,
        array $expectedCallbacks,
        array $expectedRetrieveResourceMessages
    ) {
        $callbackManager = self::$container->get(CallbackManager::class);

        $messageBus = \Mockery::spy(MessageBusInterface::class);

        $controller = self::$container->get(RequestController::class);
        $this->setControllerMessageBus($controller, $messageBus);

        foreach ($requestDataCollection as $requestIndex => $requestData) {
            $expectedResponseData = $expectedResponseDataCollection[$requestIndex];

            /* @var JsonResponse $response */
            $response = $controller->requestAction(new Request([], $requestData));

            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $this->assertEquals($expectedResponseData, json_decode($response->getContent()));
        }

        $this->assertNotEmpty($expectedCallbacks);

        foreach ($expectedCallbacks as $expectedCallback) {
            $callback = $callbackManager->findByRequestHashAndUrl(
                $expectedCallback->getRequestHash(),
                $expectedCallback->getUrl()
            );
            $this->assertInstanceOf(CallbackEntity::class, $callback);
        }

        $this->messageIndex = 0;
        $expectedDispatchCallCount = count($expectedRetrieveResourceMessages);

        $messageBus
            ->shouldHaveReceived('dispatch')
            ->times($expectedDispatchCallCount)
            ->withArgs(function (RetrieveResource $retrieveResourceMessage) use ($expectedRetrieveResourceMessages) {
                $expectedRetrieveResourceMessage = $expectedRetrieveResourceMessages[$this->messageIndex];
                $this->messageIndex++;

                $this->assertEquals($expectedRetrieveResourceMessage, $retrieveResourceMessage);

                return true;
            });
    }

    public function successfulRequestsFromEmptyDataProvider(): array
    {
        $headers = [
            'a=b' => ['a' => 'b'],
            'c=d' => ['c' => 'd'],
        ];

        $requestHashes = [
            '1.example.com headers=[]' => $this->createRequestHash(self::URL_1_EXAMPLE_COM),
            '2.example.com headers=[]' => $this->createRequestHash(self::URL_2_EXAMPLE_COM),
            '1.example.com headers=[a=b]' => $this->createRequestHash(self::URL_1_EXAMPLE_COM, $headers['a=b']),
            '1.example.com headers=[c=d]' => $this->createRequestHash(self::URL_1_EXAMPLE_COM, $headers['c=d']),
            '2.example.com headers=[a=b]' => $this->createRequestHash(self::URL_2_EXAMPLE_COM, $headers['c=d']),
            '2.example.com headers=[c=d]' => $this->createRequestHash(self::URL_2_EXAMPLE_COM, $headers['c=d']),
        ];

        return [
            'single request' => [
                'requestDataCollection' => [
                    [
                        'url' => self::URL_1_EXAMPLE_COM,
                        'callback' => 'http://callback.example.com/',
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['1.example.com headers=[]'],
                ],
                'expectedCallbacks' => [
                    $this->createCallback(
                        $requestHashes['1.example.com headers=[]'],
                        'http://callback.example.com/'
                    ),
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource($requestHashes['1.example.com headers=[]'], self::URL_1_EXAMPLE_COM),
                ],
            ],
            'two non-identical requests (different url, no headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => self::URL_1_EXAMPLE_COM,
                        'callback' => 'http://foo.example.com/',
                    ],
                    [
                        'url' => self::URL_2_EXAMPLE_COM,
                        'callback' => 'http://bar.example.com/',
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['1.example.com headers=[]'],
                    $requestHashes['2.example.com headers=[]'],
                ],
                'expectedCallbacks' => [
                    $this->createCallback(
                        $requestHashes['1.example.com headers=[]'],
                        'http://foo.example.com/'
                    ),
                    $this->createCallback(
                        $requestHashes['2.example.com headers=[]'],
                        'http://bar.example.com/'
                    ),
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource($requestHashes['1.example.com headers=[]'], self::URL_1_EXAMPLE_COM),
                    new RetrieveResource($requestHashes['2.example.com headers=[]'], self::URL_2_EXAMPLE_COM),
                ],
            ],
            'two non-identical requests (same url, different headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => self::URL_1_EXAMPLE_COM,
                        'callback' => 'http://foo.example.com/',
                        'headers' => $headers['a=b'],
                    ],
                    [
                        'url' => self::URL_1_EXAMPLE_COM,
                        'callback' => 'http://bar.example.com/',
                        'headers' => $headers['c=d'],
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['1.example.com headers=[a=b]'],
                    $requestHashes['1.example.com headers=[c=d]'],
                ],
                'expectedCallbacks' => [
                    $this->createCallback(
                        $requestHashes['1.example.com headers=[a=b]'],
                        'http://foo.example.com/'
                    ),
                    $this->createCallback(
                        $requestHashes['1.example.com headers=[c=d]'],
                        'http://bar.example.com/'
                    ),
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource(
                        $requestHashes['1.example.com headers=[a=b]'],
                        self::URL_1_EXAMPLE_COM,
                        new Headers($headers['a=b'])
                    ),
                    new RetrieveResource(
                        $requestHashes['1.example.com headers=[c=d]'],
                        self::URL_1_EXAMPLE_COM,
                        new Headers($headers['c=d'])
                    ),
                ],
            ],
            'two non-identical requests (different url, different headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => self::URL_1_EXAMPLE_COM,
                        'callback' => 'http://foo.example.com/',
                        'headers' => $headers['a=b'],
                    ],
                    [
                        'url' => self::URL_2_EXAMPLE_COM,
                        'callback' => 'http://bar.example.com/',
                        'headers' => $headers['c=d'],
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['1.example.com headers=[a=b]'],
                    $requestHashes['2.example.com headers=[c=d]'],
                ],
                'expectedCallbacks' => [
                    $this->createCallback(
                        $requestHashes['1.example.com headers=[a=b]'],
                        'http://foo.example.com/'
                    ),
                    $this->createCallback(
                        $requestHashes['2.example.com headers=[c=d]'],
                        'http://bar.example.com/'
                    ),
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource(
                        $requestHashes['1.example.com headers=[a=b]'],
                        self::URL_1_EXAMPLE_COM,
                        new Headers($headers['a=b'])
                    ),
                    new RetrieveResource(
                        $requestHashes['2.example.com headers=[c=d]'],
                        self::URL_2_EXAMPLE_COM,
                        new Headers($headers['c=d'])
                    ),
                ],
            ],
            'two identical requests (same url, no headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => self::URL_1_EXAMPLE_COM,
                        'callback' => 'http://callback.example.com/',
                        'headers' => [],
                    ],
                    [
                        'url' => self::URL_1_EXAMPLE_COM,
                        'callback' => 'http://callback.example.com/',
                        'headers' => [],
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['1.example.com headers=[]'],
                    $requestHashes['1.example.com headers=[]'],
                ],
                'expectedCallbacks' => [
                    $this->createCallback(
                        $requestHashes['1.example.com headers=[]'],
                        'http://callback.example.com/'
                    ),
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource($requestHashes['1.example.com headers=[]'], self::URL_1_EXAMPLE_COM),
                    new RetrieveResource($requestHashes['1.example.com headers=[]'], self::URL_1_EXAMPLE_COM),
                ],
            ],
            'two identical requests (same url, same headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => self::URL_1_EXAMPLE_COM,
                        'callback' => 'http://callback.example.com/',
                        'headers' => $headers['a=b'],
                    ],
                    [
                        'url' => self::URL_1_EXAMPLE_COM,
                        'callback' => 'http://callback.example.com/',
                        'headers' => $headers['a=b'],
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['1.example.com headers=[a=b]'],
                    $requestHashes['1.example.com headers=[a=b]'],
                ],
                'expectedCallbacks' => [
                    $this->createCallback(
                        $requestHashes['1.example.com headers=[a=b]'],
                        'http://callback.example.com/'
                    ),
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource(
                        $requestHashes['1.example.com headers=[a=b]'],
                        self::URL_1_EXAMPLE_COM,
                        new Headers($headers['a=b'])
                    ),
                    new RetrieveResource(
                        $requestHashes['1.example.com headers=[a=b]'],
                        self::URL_1_EXAMPLE_COM,
                        new Headers($headers['a=b'])
                    ),
                ],
            ],
        ];
    }

    /**
     * @dataProvider setCallbackLogResponseDataProvider
     *
     * @param array $requestDataCollection
     * @param CallbackEntity $expectedCallback
     */
    public function testSetCallbackLogResponse(
        array $requestDataCollection,
        CallbackEntity $expectedCallback
    ) {
        $callbackManager = self::$container->get(CallbackManager::class);

        $messageBus = \Mockery::spy(MessageBusInterface::class);

        $controller = self::$container->get(RequestController::class);
        $this->setControllerMessageBus($controller, $messageBus);

        foreach ($requestDataCollection as $requestIndex => $requestData) {
            /* @var JsonResponse $response */
            $controller->requestAction(new Request([], $requestData));
        }

        $callback = $callbackManager->findByRequestHashAndUrl(
            $expectedCallback->getRequestHash(),
            $expectedCallback->getUrl()
        );

        $this->assertInstanceOf(CallbackEntity::class, $callback);
        $this->assertEquals($expectedCallback->getLogResponse(), $callback->getLogResponse());
    }

    public function setCallbackLogResponseDataProvider(): array
    {
        $url = self::URL_1_EXAMPLE_COM;
        $callbackUrl = 'http://callback.example.com/';

        $requestData = [
            'url' => $url,
            'callback' => $callbackUrl,
        ];

        $requestHash = $this->createRequestHash($url);

        return [
            'single request; log-callback-response: missing' => [
                'requestDataCollection' => [
                    $requestData,
                ],
                'expectedCallback' => $this->createCallback($requestHash, $callbackUrl, false),
            ],
            'single request; log-callback-response: false' => [
                'requestDataCollection' => [
                    array_merge($requestData, [
                        'log-callback-response' => false,
                    ]),
                ],
                'expectedCallback' => $this->createCallback($requestHash, $callbackUrl, false),
            ],
            'single request; log-callback-response: true' => [
                'requestDataCollection' => [
                    array_merge($requestData, [
                        'log-callback-response' => true,
                    ]),
                ],
                'expectedCallback' => $this->createCallback($requestHash, $callbackUrl, true),
            ],
            'two identical requests; log-callback-response: missing, missing' => [
                'requestDataCollection' => [
                    $requestData,
                    $requestData,
                ],
                'expectedCallback' => $this->createCallback($requestHash, $callbackUrl, false),
            ],
            'two identical requests; log-callback-response: true, missing' => [
                'requestDataCollection' => [
                    array_merge($requestData, [
                        'log-callback-response' => true,
                    ]),
                    $requestData,
                ],
                'expectedCallback' => $this->createCallback($requestHash, $callbackUrl, false),
            ],
            'two identical requests; log-callback-response: false, missing' => [
                'requestDataCollection' => [
                    array_merge($requestData, [
                        'log-callback-response' => false,
                    ]),
                    $requestData,
                ],
                'expectedCallback' => $this->createCallback($requestHash, $callbackUrl, false),
            ],
            'two identical requests; log-callback-response: missing, false' => [
                'requestDataCollection' => [
                    $requestData,
                    array_merge($requestData, [
                        'log-callback-response' => false,
                    ]),
                ],
                'expectedCallback' => $this->createCallback($requestHash, $callbackUrl, false),
            ],
            'two identical requests; log-callback-response: missing, true' => [
                'requestDataCollection' => [
                    $requestData,
                    array_merge($requestData, [
                        'log-callback-response' => true,
                    ]),
                ],
                'expectedCallback' => $this->createCallback($requestHash, $callbackUrl, true),
            ],
            'two identical requests; log-callback-response: true, false' => [
                'requestDataCollection' => [
                    array_merge($requestData, [
                        'log-callback-response' => true,
                    ]),
                    array_merge($requestData, [
                        'log-callback-response' => false,
                    ]),
                ],
                'expectedCallback' => $this->createCallback($requestHash, $callbackUrl, false),
            ],
            'two identical requests; log-callback-response: false, true' => [
                'requestDataCollection' => [
                    array_merge($requestData, [
                        'log-callback-response' => false,
                    ]),
                    array_merge($requestData, [
                        'log-callback-response' => true,
                    ]),
                ],
                'expectedCallback' => $this->createCallback($requestHash, $callbackUrl, true),
            ],
        ];
    }

    /**
     * @dataProvider successfulRequestWithNonMatchingCachedResourcesDataProvider
     *
     * @param CachedResource[] $cachedResourceCollection
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testSuccessfulRequestWithNonMatchingCachedResources(array $cachedResourceCollection)
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);

        foreach ($cachedResourceCollection as $cachedResource) {
            $entityManager->persist($cachedResource);
            $entityManager->flush();
        }

        $messageBus = \Mockery::spy(MessageBusInterface::class);

        $controller = self::$container->get(RequestController::class);
        $this->setControllerMessageBus($controller, $messageBus);

        $url = 'http://example.com/';
        $requestHash = $this->createRequestHash($url);

        $controller->requestAction(new Request([], [
            'url' => $url,
            'callback' => 'http://callback.example.com/',
        ]));

        $expectedRetrieveResourceMessage = new RetrieveResource($requestHash, $url);

        $messageBus
            ->shouldHaveReceived('dispatch')
            ->withArgs(function (RetrieveResource $retrieveResourceMessage) use ($expectedRetrieveResourceMessage) {
                $this->assertEquals($expectedRetrieveResourceMessage, $retrieveResourceMessage);

                return true;
            });
    }

    public function successfulRequestWithNonMatchingCachedResourcesDataProvider(): array
    {
        return [
            'no matching existing cached resource' => [
                'cachedResourceCollection' => [
                    $this->createCachedResource('non-matching-hash', new \DateTime()),
                ],
            ],
            'matches existing cached resource; resource is stale' => [
                'cachedResourceCollection' => [
                    $this->createCachedResource(
                        $this->createRequestHash('http://example.com/'),
                        new \DateTime('-1 year')
                    ),
                ],
            ],
        ];
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testSuccessfulRequestWithMatchingCachedResource()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $requestHash = $this->createRequestHash('http://example.com/');

        $cachedResource = $this->createCachedResource($requestHash, new \DateTime());
        $entityManager->persist($cachedResource);
        $entityManager->flush();

        $messageBus = \Mockery::spy(MessageBusInterface::class);

        $controller = self::$container->get(RequestController::class);
        $this->setControllerMessageBus($controller, $messageBus);

        $controller->requestAction(new Request([], [
            'url' => 'http://example.com/',
            'callback' => 'http://callback.example.com/',
        ]));

        $messageBus
            ->shouldHaveReceived('dispatch')
            ->withArgs(function (SendResponse $sendResponseMessage) use ($requestHash) {
                $responseData = $sendResponseMessage->getResponseData();

                $this->assertEquals($requestHash, $responseData['request_id']);

                return true;
            });
    }

//    public function testSetLogCallbackResponse()
//    {
//        $messageBus = \Mockery::spy(MessageBusInterface::class);
//
//        $controller = self::$container->get(RequestController::class);
//        $this->setControllerMessageBus($controller, $messageBus);
//
//        $controller->requestAction(new Request([], [
//            'url' => 'http://example.com/',
//            'callback' => 'http://callback.example.com/',
//            'log-callback-response' => 1,
//        ]));
//
//        $this->assertTrue(true);
//    }

    private function createRequestHash(string $url, array $headers = []): string
    {
        $identifier = new RequestIdentifier($url, new Headers($headers));

        return $identifier->getHash();
    }

    private function createCachedResource(string $requestHash, \DateTime $lastStored): CachedResource
    {
        $cachedResource = new CachedResource();
        $cachedResource->setRequestHash($requestHash);
        $cachedResource->setLastStored($lastStored);

        return $cachedResource;
    }

    private function setControllerMessageBus(RequestController $controller, MessageBusInterface $messageBus)
    {
        try {
            $reflector = new \ReflectionClass(RequestController::class);
            $property = $reflector->getProperty('messageBus');
            $property->setAccessible(true);
            $property->setValue($controller, $messageBus);
        } catch (\ReflectionException $exception) {
        }
    }

    private function createCallback(string $requestHash, string $url, ?bool $logResponse = false): CallbackEntity
    {
        $callback = new CallbackEntity();

        $callback->setRequestHash($requestHash);
        $callback->setUrl($url);
        $callback->setLogResponse($logResponse);

        return $callback;
    }
}
