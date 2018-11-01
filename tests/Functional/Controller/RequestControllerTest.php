<?php

namespace App\Tests\Functional\Controller;

use App\Controller\RequestController;
use App\Entity\CachedResource;
use App\Entity\Callback;
use App\Message\RetrieveResource;
use App\Message\SendResponse;
use App\Model\RequestIdentifier;
use App\Model\Response\RebuildableDecoratedResponse;
use App\Services\CallbackManager;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use webignition\HttpHeaders\Headers;

class RequestControllerTest extends AbstractFunctionalTestCase
{
    const ROUTE_NAME = 'get';

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
        $this->expectException(MethodNotAllowedHttpException::class);

        $this->client->request('GET', $this->routeUrl);
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
     * @param array $expectedCallbacks
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
            $requestHash = $expectedCallback['requestHash'];
            $url = $expectedCallback['url'];

            $callback = $callbackManager->findByRequestHashAndUrl($requestHash, $url);
            $this->assertInstanceOf(Callback::class, $callback);
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
        $urls = [
            'r1.example.com' => 'http://r1.example.com/',
            'r2.example.com' => 'http://r2.example.com/',
        ];

        $headers = [
            'a=b' => ['a' => 'b'],
            'c=d' => ['c' => 'd'],
        ];

        $requestHashes = [
            'r1.example.com headers=[]' => $this->createRequestHash($urls['r1.example.com']),
            'r2.example.com headers=[]' => $this->createRequestHash($urls['r2.example.com']),
            'r1.example.com headers=[a=b]' => $this->createRequestHash($urls['r1.example.com'], $headers['a=b']),
            'r1.example.com headers=[c=d]' => $this->createRequestHash($urls['r1.example.com'], $headers['c=d']),
            'r2.example.com headers=[a=b]' => $this->createRequestHash($urls['r2.example.com'], $headers['c=d']),
            'r2.example.com headers=[c=d]' => $this->createRequestHash($urls['r2.example.com'], $headers['c=d']),
        ];

        return [
            'single request' => [
                'requestDataCollection' => [
                    [
                        'url' => $urls['r1.example.com'],
                        'callback' => 'http://callback.example.com/',
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['r1.example.com headers=[]'],
                ],
                'expectedCallbacks' => [
                    [
                        'requestHash' => $requestHashes['r1.example.com headers=[]'],
                        'url' => 'http://callback.example.com/',
                    ],
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource($requestHashes['r1.example.com headers=[]'], $urls['r1.example.com']),
                ],
            ],
            'two non-identical requests (different url, no headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => $urls['r1.example.com'],
                        'callback' => 'http://foo.example.com/',
                    ],
                    [
                        'url' => $urls['r2.example.com'],
                        'callback' => 'http://bar.example.com/',
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['r1.example.com headers=[]'],
                    $requestHashes['r2.example.com headers=[]'],
                ],
                'expectedCallbacks' => [
                    [
                        'requestHash' => $requestHashes['r1.example.com headers=[]'],
                        'url' => 'http://foo.example.com/',
                    ],
                    [
                        'requestHash' => $requestHashes['r2.example.com headers=[]'],
                        'url' => 'http://bar.example.com/',
                    ],
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource($requestHashes['r1.example.com headers=[]'], $urls['r1.example.com']),
                    new RetrieveResource($requestHashes['r2.example.com headers=[]'], $urls['r2.example.com']),
                ],
            ],
            'two non-identical requests (same url, different headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => $urls['r1.example.com'],
                        'callback' => 'http://foo.example.com/',
                        'headers' => $headers['a=b'],
                    ],
                    [
                        'url' => $urls['r1.example.com'],
                        'callback' => 'http://bar.example.com/',
                        'headers' => $headers['c=d'],
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['r1.example.com headers=[a=b]'],
                    $requestHashes['r1.example.com headers=[c=d]'],
                ],
                'expectedCallbacks' => [
                    [
                        'requestHash' => $requestHashes['r1.example.com headers=[a=b]'],
                        'url' => 'http://foo.example.com/',
                    ],
                    [
                        'requestHash' => $requestHashes['r1.example.com headers=[c=d]'],
                        'url' => 'http://bar.example.com/',
                    ],
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource(
                        $requestHashes['r1.example.com headers=[a=b]'],
                        $urls['r1.example.com'],
                        new Headers($headers['a=b'])
                    ),
                    new RetrieveResource(
                        $requestHashes['r1.example.com headers=[c=d]'],
                        $urls['r1.example.com'],
                        new Headers($headers['c=d'])
                    ),
                ],
            ],
            'two non-identical requests (different url, different headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => $urls['r1.example.com'],
                        'callback' => 'http://foo.example.com/',
                        'headers' => $headers['a=b'],
                    ],
                    [
                        'url' => $urls['r2.example.com'],
                        'callback' => 'http://bar.example.com/',
                        'headers' => $headers['c=d'],
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['r1.example.com headers=[a=b]'],
                    $requestHashes['r2.example.com headers=[c=d]'],
                ],
                'expectedCallbacks' => [
                    [
                        'requestHash' => $requestHashes['r1.example.com headers=[a=b]'],
                        'url' => 'http://foo.example.com/',
                    ],
                    [
                        'requestHash' => $requestHashes['r2.example.com headers=[c=d]'],
                        'url' => 'http://bar.example.com/',
                    ],
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource(
                        $requestHashes['r1.example.com headers=[a=b]'],
                        $urls['r1.example.com'],
                        new Headers($headers['a=b'])
                    ),
                    new RetrieveResource(
                        $requestHashes['r2.example.com headers=[c=d]'],
                        $urls['r2.example.com'],
                        new Headers($headers['c=d'])
                    ),
                ],
            ],
            'two identical requests (same url, no headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => $urls['r1.example.com'],
                        'callback' => 'http://callback.example.com/',
                        'headers' => [],
                    ],
                    [
                        'url' => $urls['r1.example.com'],
                        'callback' => 'http://callback.example.com/',
                        'headers' => [],
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['r1.example.com headers=[]'],
                    $requestHashes['r1.example.com headers=[]'],
                ],
                'expectedCallbacks' => [
                    [
                        'requestHash' => $requestHashes['r1.example.com headers=[]'],
                        'url' => 'http://callback.example.com/',
                    ],
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource($requestHashes['r1.example.com headers=[]'], $urls['r1.example.com']),
                    new RetrieveResource($requestHashes['r1.example.com headers=[]'], $urls['r1.example.com']),
                ],
            ],
            'two identical requests (same url, same headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => $urls['r1.example.com'],
                        'callback' => 'http://callback.example.com/',
                        'headers' => $headers['a=b'],
                    ],
                    [
                        'url' => $urls['r1.example.com'],
                        'callback' => 'http://callback.example.com/',
                        'headers' => $headers['a=b'],
                    ],
                ],
                'expectedResponseDataCollection' => [
                    $requestHashes['r1.example.com headers=[a=b]'],
                    $requestHashes['r1.example.com headers=[a=b]'],
                ],
                'expectedCallbacks' => [
                    [
                        'requestHash' => $requestHashes['r1.example.com headers=[a=b]'],
                        'url' => 'http://callback.example.com/',
                    ],
                ],
                'expectedRetrieveResourceMessages' => [
                    new RetrieveResource(
                        $requestHashes['r1.example.com headers=[a=b]'],
                        $urls['r1.example.com'],
                        new Headers($headers['a=b'])
                    ),
                    new RetrieveResource(
                        $requestHashes['r1.example.com headers=[a=b]'],
                        $urls['r1.example.com'],
                        new Headers($headers['a=b'])
                    ),
                ],
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
}
