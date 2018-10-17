<?php

namespace App\Tests\Functional\Controller;

use App\Controller\RequestController;
use App\Entity\RetrieveRequest;
use App\Resque\Job\RetrieveResourceJob;
use App\Services\ResqueQueueService;
use App\Services\RetrieveRequestHashFactory;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\RouterInterface;

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
     * @dataProvider successfulRequestsDataProvider
     *
     * @param array $requestDataCollection
     * @param array $expectedRetrieveRequestDataCollection
     */
    public function testSuccessfulRequests(array $requestDataCollection, array $expectedRetrieveRequestDataCollection)
    {
        $this->clearRedis();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $resqueQueueService = self::$container->get(ResqueQueueService::class);
        $retrieveRequestRepository = $entityManager->getRepository(RetrieveRequest::class);

        $this->assertTrue($resqueQueueService->isEmpty(RetrieveResourceJob::QUEUE_NAME));

        $controller = self::$container->get(RequestController::class);

        foreach ($requestDataCollection as $requestData) {
            $response = $controller->requestAction(new Request([], $requestData));

            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }

        $this->assertFalse($resqueQueueService->isEmpty(RetrieveResourceJob::QUEUE_NAME));
        $this->assertNotEmpty($expectedRetrieveRequestDataCollection);

        foreach ($expectedRetrieveRequestDataCollection as $hash => $expectedRetrieveRequestData) {
            /* @var RetrieveRequest $retrieveRequest */
            $retrieveRequest = $retrieveRequestRepository->findOneBy([
                'hash' => $hash,
            ]);

            $this->assertInstanceOf(RetrieveRequest::class, $retrieveRequest);
            $this->assertEquals($expectedRetrieveRequestData['url'], $retrieveRequest->getUrl());
            $this->assertEquals($expectedRetrieveRequestData['callbackUrls'], $retrieveRequest->getCallbackUrls());
            $this->assertTrue($resqueQueueService->contains(
                RetrieveResourceJob::QUEUE_NAME,
                ['id' => $retrieveRequest->getId()]
            ));
        }
    }

    public function successfulRequestsDataProvider(): array
    {
        return [
            'single request' => [
                'requestDataCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'callback' => 'http://callback.example.com/',
                        'headers' => [],
                    ],
                ],
                'expectedRetrieveRequestDataCollection' => [
                    RetrieveRequestHashFactory::create('http://example.com/', []) => [
                        'url' => 'http://example.com/',
                        'callbackUrls' => [
                            'http://callback.example.com/',
                        ],
                        'headers' => [],
                    ],
                ],
            ],
            'two non-identical requests (different url, no headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => 'http://one.example.com/',
                        'callback' => 'http://foo.example.com/',
                        'headers' => [],
                    ],
                    [
                        'url' => 'http://two.example.com/',
                        'callback' => 'http://bar.example.com/',
                        'headers' => [],
                    ],
                ],
                'expectedRetrieveRequestDataCollection' => [
                    RetrieveRequestHashFactory::create('http://one.example.com/', []) => [
                        'url' => 'http://one.example.com/',
                        'callbackUrls' => [
                            'http://foo.example.com/',
                        ],
                        'headers' => [],
                    ],
                    RetrieveRequestHashFactory::create('http://two.example.com/', []) => [
                        'url' => 'http://two.example.com/',
                        'callbackUrls' => [
                            'http://bar.example.com/',
                        ],
                        'headers' => [],
                    ],
                ],
            ],
            'two non-identical requests (same url, different headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => 'http://one.example.com/',
                        'callback' => 'http://foo.example.com/',
                        'headers' => [
                            'foo' => 'bar',
                        ],
                    ],
                    [
                        'url' => 'http://one.example.com/',
                        'callback' => 'http://bar.example.com/',
                        'headers' => [
                            'fizz' => 'buzz',
                        ],
                    ],
                ],
                'expectedRetrieveRequestDataCollection' => [
                    RetrieveRequestHashFactory::create('http://one.example.com/', ['foo' => 'bar']) => [
                        'url' => 'http://one.example.com/',
                        'callbackUrls' => [
                            'http://foo.example.com/',
                        ],
                        'headers' => [
                            'foo' => 'bar',
                        ],
                    ],
                    RetrieveRequestHashFactory::create('http://one.example.com/', ['fizz' => 'buzz']) => [
                        'url' => 'http://one.example.com/',
                        'callbackUrls' => [
                            'http://bar.example.com/',
                        ],
                        'headers' => [
                            'fizz' => 'buzz',
                        ],
                    ],
                ],
            ],
            'two non-identical requests (different url, different headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => 'http://one.example.com/',
                        'callback' => 'http://foo.example.com/',
                        'headers' => [
                            'foo' => 'bar',
                        ],
                    ],
                    [
                        'url' => 'http://two.example.com/',
                        'callback' => 'http://bar.example.com/',
                        'headers' => [
                            'fizz' => 'buzz',
                        ],
                    ],
                ],
                'expectedRetrieveRequestDataCollection' => [
                    RetrieveRequestHashFactory::create('http://one.example.com/', ['foo' => 'bar']) => [
                        'url' => 'http://one.example.com/',
                        'callbackUrls' => [
                            'http://foo.example.com/',
                        ],
                        'headers' => [
                            'foo' => 'bar',
                        ],
                    ],
                    RetrieveRequestHashFactory::create('http://two.example.com/', ['fizz' => 'buzz']) => [
                        'url' => 'http://two.example.com/',
                        'callbackUrls' => [
                            'http://bar.example.com/',
                        ],
                        'headers' => [
                            'fizz' => 'buzz',
                        ],
                    ],
                ],
            ],
            'two identical requests (same url, no headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'callback' => 'http://callback.example.com/',
                        'headers' => [],
                    ],
                    [
                        'url' => 'http://example.com/',
                        'callback' => 'http://callback.example.com/',
                        'headers' => [],
                    ],
                ],
                'expectedRetrieveRequestDataCollection' => [
                    RetrieveRequestHashFactory::create('http://example.com/', []) => [
                        'url' => 'http://example.com/',
                        'callbackUrls' => [
                            'http://callback.example.com/',
                        ],
                        'headers' => [],
                    ],
                ],
            ],
            'two identical requests (same url, same headers)' => [
                'requestDataCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'callback' => 'http://callback.example.com/',
                        'headers' => [
                            'foo' => 'bar',
                        ],
                    ],
                    [
                        'url' => 'http://example.com/',
                        'callback' => 'http://callback.example.com/',
                        'headers' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
                'expectedRetrieveRequestDataCollection' => [
                    RetrieveRequestHashFactory::create('http://example.com/', ['foo' => 'bar']) => [
                        'url' => 'http://example.com/',
                        'callbackUrls' => [
                            'http://callback.example.com/',
                        ],
                        'headers' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ],
        ];
    }
}
