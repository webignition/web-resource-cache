<?php

namespace App\Tests\Functional\Controller;

use App\Controller\GetController;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\RouterInterface;

class GetControllerTest extends AbstractFunctionalTestCase
{
    public function testRouteWithGetRequest()
    {
        /* @var RouterInterface $router */
        $router = self::$container->get(RouterInterface::class);

        $this->expectException(MethodNotAllowedHttpException::class);

        $this->client->request('GET', $router->generate('get'));
    }

    public function testPostRequest()
    {
        /* @var GetController $controller */
        $controller = self::$container->get(GetController::class);

        $request = new Request([], [
            'foo' => 'bar',
        ]);

        $response = $controller->get($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
