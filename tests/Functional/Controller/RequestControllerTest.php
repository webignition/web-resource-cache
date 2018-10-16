<?php

namespace App\Tests\Functional\Controller;

use App\Controller\RequestController;
use App\Entity\RetrieveRequest;
use App\Resque\Job\GetResourceJob;
use App\Services\ResqueQueueService;
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

    public function testSuccessfulRequest()
    {
        $this->clearRedis();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $resqueQueueService = self::$container->get(ResqueQueueService::class);
        $retrieveRequestRepository = $entityManager->getRepository(RetrieveRequest::class);

        $this->assertTrue($resqueQueueService->isEmpty(GetResourceJob::QUEUE_NAME));

        /* @var RequestController $controller */
        $controller = self::$container->get(RequestController::class);

        $url = 'http://example.com/';

        $requestData = [
            'url' => $url,
            'callback' => 'http://callback.example.com/',
        ];

        $request = new Request([], $requestData);
        $response = $controller->requestAction($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $retrievedGetRequest = $retrieveRequestRepository->findOneBy([
            'url' => $url,
        ]);
        $this->assertInstanceOf(RetrieveRequest::class, $retrievedGetRequest);
        $this->assertFalse($resqueQueueService->isEmpty(GetResourceJob::QUEUE_NAME));
        $this->assertTrue($resqueQueueService->contains(
            GetResourceJob::QUEUE_NAME,
            ['id' => $retrievedGetRequest->getId()]
        ));
    }
}
