<?php

namespace App\Tests\Functional\Controller;

use App\Controller\GetController;
use App\Entity\GetRequest;
use App\Services\Whitelist;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\RouterInterface;

class GetControllerTest extends AbstractFunctionalTestCase
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
        /* @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $getRequestRepository = $entityManager->getRepository(GetRequest::class);

        /* @var GetController $controller */
        $controller = self::$container->get(GetController::class);

        $url = 'http://example.com/';

        $requestData = [
            'url' => $url,
            'callback' => 'http://callback.example.com/',
        ];

        $request = new Request([], $requestData);
        $response = $controller->getAction($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $retrievedGetRequest = $getRequestRepository->findOneBy([
            'url' => $url,
        ]);

        $this->assertInstanceOf(GetRequest::class, $retrievedGetRequest);
    }
}
