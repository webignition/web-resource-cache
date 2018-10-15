<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GetController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetRequest()
    {
        $controller = new GetController();

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);

        $response = $controller->getAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
