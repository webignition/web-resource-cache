<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GetController;
use App\Services\Whitelist;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider invalidRequestDataProvider
     *
     * @param array $requestData
     */
    public function testInvalidRequest(Whitelist $callbackUrlWhitelist, array $requestData)
    {
        $controller = new GetController();

        $request = new Request([], $requestData);
        $response = $controller->getAction($callbackUrlWhitelist, $request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function invalidRequestDataProvider(): array
    {
        return [
            'empty request' => [
                'callbackUrlWhitelist' => new Whitelist([]),
                'requestData' => [],
            ],
            'missing callback url' => [
                'callbackUrlWhitelist' => new Whitelist([]),
                'requestData' => [
                    'uri' => 'http://example.com/',
                ],
            ],
            'non-whitelisted callback url (empty)' => [
                'callbackUrlWhitelist' => new Whitelist([]),
                'requestData' => [
                    'uri' => 'http://example.com/',
                    'callback' => '',
                ],
            ],
            'non-whitelisted callback url (non-matching)' => [
                'callbackUrlWhitelist' => new Whitelist([
                    '/^http:\/\/[a-z]+\.example\.com\/$/',
                ]),
                'requestData' => [
                    'uri' => 'http://example.com/',
                    'callback' => 'http://example.com/callback',
                ],
            ],
        ];
    }
}
