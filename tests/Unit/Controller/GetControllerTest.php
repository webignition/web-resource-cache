<?php

namespace App\Tests\Unit\Controller;

use App\Controller\GetController;
use App\Services\GetRequestManager;
use App\Services\Whitelist;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider invalidRequestDataProvider
     *
     * @param Whitelist $callbackUrlWhitelist
     * @param array $requestData
     */
    public function testInvalidRequest(Whitelist $callbackUrlWhitelist, array $requestData)
    {
        /* @var GetRequestManager $getRequestManager */
        $getRequestManager = \Mockery::mock(GetRequestManager::class);

        $controller = new GetController($callbackUrlWhitelist, $getRequestManager);

        $request = new Request([], $requestData);
        $response = $controller->getAction($request);

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
