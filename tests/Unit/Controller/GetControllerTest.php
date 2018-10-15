<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GetController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider invalidRequestDataProvider
     *
     * @param array $requestData
     */
    public function testInvalidRequest(array $requestData)
    {
        $controller = new GetController();

        $request = new Request([], $requestData);
        $response = $controller->getAction($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function invalidRequestDataProvider(): array
    {
        return [
            'empty request' => [
                'requestData' => [],
            ],
            'missing callback url' => [
                'requestData' => [
                    'uri' => 'http://example.com/',
                ],
            ],
        ];
    }
}
