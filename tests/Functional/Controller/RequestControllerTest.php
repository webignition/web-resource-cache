<?php

namespace App\Tests\Functional\Controller;

use App\Controller\RequestController;
use App\Entity\CachedResource;
use App\Entity\Callback;
use App\Model\RequestIdentifier;
use App\Model\RetrieveRequest;
use App\Resque\Job\RetrieveResourceJob;
use App\Resque\Job\SendResponseJob;
use App\Services\CallbackManager;
use App\Services\ResqueQueueService;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\RouterInterface;
use webignition\HttpHeaders\Headers;

class RequestControllerTest extends AbstractFunctionalTestCase
{
    const ROUTE_NAME = 'get';

    /**
     * @var string
     */
    private $routeUrl;

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
     * @param array $expectedRetrieveResourceJobs
     */
    public function testSuccessfulRequestsFromEmpty(
        array $requestDataCollection,
        array $expectedResponseDataCollection,
        array $expectedCallbacks,
        array $expectedRetrieveResourceJobs
    ) {
        $this->clearRedis();

        $resqueQueueService = self::$container->get(ResqueQueueService::class);
        $callbackManager = self::$container->get(CallbackManager::class);

        $this->assertTrue($resqueQueueService->isEmpty(RetrieveResourceJob::QUEUE_NAME));

        $controller = self::$container->get(RequestController::class);

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

        $this->assertNotEmpty($expectedRetrieveResourceJobs);
        $this->assertFalse($resqueQueueService->isEmpty(RetrieveResourceJob::QUEUE_NAME));

        foreach ($expectedRetrieveResourceJobs as $expectedRetrieveResourceJob) {
            $this->assertTrue($resqueQueueService->contains($expectedRetrieveResourceJob));
        }
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
                        'headers' => [],
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
                'expectedRetrieveResourceJobs' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            $requestHashes['r1.example.com headers=[]'],
                            $urls['r1.example.com']
                        )),
                    ]),
                ],
            ],
            'r2.example.com non-identical requests (different url, no headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => $urls['r1.example.com'],
                        'callback' => 'http://foo.example.com/',
                        'headers' => [],
                    ],
                    [
                        'url' => $urls['r2.example.com'],
                        'callback' => 'http://bar.example.com/',
                        'headers' => [],
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
                'expectedRetrieveResourceJobs' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            $requestHashes['r1.example.com headers=[]'],
                            $urls['r1.example.com']
                        )),
                    ]),
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            $requestHashes['r2.example.com headers=[]'],
                            $urls['r2.example.com']
                        )),
                    ]),
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
                'expectedRetrieveResourceJobs' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            $requestHashes['r1.example.com headers=[a=b]'],
                            $urls['r1.example.com'],
                            new Headers($headers['a=b'])
                        )),
                    ]),
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            $requestHashes['r1.example.com headers=[c=d]'],
                            $urls['r1.example.com'],
                            new Headers($headers['c=d'])
                        )),
                    ]),
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
                'expectedRetrieveResourceJobs' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            $requestHashes['r1.example.com headers=[a=b]'],
                            $urls['r1.example.com'],
                            new Headers($headers['a=b'])
                        )),
                    ]),
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            $requestHashes['r2.example.com headers=[c=d]'],
                            $urls['r2.example.com'],
                            new Headers($headers['c=d'])
                        )),
                    ]),
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
                'expectedRetrieveResourceJobs' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            $requestHashes['r1.example.com headers=[]'],
                            $urls['r1.example.com']
                        )),
                    ]),
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
                'expectedRetrieveResourceJobs' => [
                    new RetrieveResourceJob([
                        'request-json' => json_encode(new RetrieveRequest(
                            $requestHashes['r1.example.com headers=[a=b]'],
                            $urls['r1.example.com'],
                            new Headers($headers['a=b'])
                        )),
                    ]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider successfulRequestWithCachedResourcesDataProvider
     *
     * @param CachedResource[] $cachedResourceCollection
     * @param array $requestData
     * @param bool $expectedHasSendResponseJob
     * @param bool $expectedHasRetrieveResourceJob
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testSuccessfulRequestWithCachedResources(
        array $cachedResourceCollection,
        array $requestData,
        bool $expectedHasSendResponseJob,
        bool $expectedHasRetrieveResourceJob
    ) {
        $this->clearRedis();

        $resqueQueueService = self::$container->get(ResqueQueueService::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $this->assertTrue($resqueQueueService->isEmpty(RetrieveResourceJob::QUEUE_NAME));
        $this->assertTrue($resqueQueueService->isEmpty(SendResponseJob::QUEUE_NAME));

        foreach ($cachedResourceCollection as $cachedResource) {
            $entityManager->persist($cachedResource);
            $entityManager->flush();
        }

        $controller = self::$container->get(RequestController::class);
        $controller->requestAction(new Request([], $requestData));

        $this->assertEquals(
            !$expectedHasSendResponseJob,
            $resqueQueueService->isEmpty(SendResponseJob::QUEUE_NAME)
        );

        $this->assertEquals(
            !$expectedHasRetrieveResourceJob,
            $resqueQueueService->isEmpty(RetrieveResourceJob::QUEUE_NAME)
        );
    }

    public function successfulRequestWithCachedResourcesDataProvider(): array
    {
        return [
            'request not matches existing cached resource' => [
                'cachedResourceCollection' => [
                    $this->createCachedResource('non-matching-hash', new \DateTime()),
                ],
                'requestData' => [
                    'url' => 'http://example.com/',
                    'callback' => 'http://callback.example.com/',
                ],
                'expectedHasSendResponseJob' => false,
                'expectedHasRetrieveResourceJob' => true,
            ],
            'request matches existing cached resource; resource is stale' => [
                'cachedResourceCollection' => [
                    $this->createCachedResource(
                        $this->createRequestHash('http://example.com/'),
                        new \DateTime('-1 year')
                    ),
                ],
                'requestData' => [
                    'url' => 'http://example.com/',
                    'callback' => 'http://callback.example.com/',
                ],
                'expectedHasSendResponseJob' => false,
                'expectedHasRetrieveResourceJob' => true,
            ],
            'request matches existing cached resource; resource is fresh' => [
                'cachedResourceCollection' => [
                    $this->createCachedResource(
                        $this->createRequestHash('http://example.com/'),
                        new \DateTime()
                    ),
                ],
                'requestData' => [
                    'url' => 'http://example.com/',
                    'callback' => 'http://callback.example.com/',
                ],
                'expectedHasSendResponseJob' => true,
                'expectedHasRetrieveResourceJob' => false,
            ],
        ];
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
}
