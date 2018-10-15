<?php

namespace App\Tests\Functional\Controller;

use App\Controller\GetController;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetControllerTest extends AbstractFunctionalTestCase
{
    public function testGetRequest()
    {
        /* @var GetController $controller */
        $controller = self::$container->get(GetController::class);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);

        $response = $controller->get($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
