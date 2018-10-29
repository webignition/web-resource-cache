<?php

namespace App\Tests\Unit\Controller;

use App\Controller\RequestController;
use App\Services\CachedResourceManager;
use App\Services\CachedResourceValidator;
use App\Services\CallbackFactory;
use App\Services\CallbackManager;
use App\Services\RetrieveResourceJobManager;
use App\Services\Whitelist;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider invalidRequestDataProvider
     *
     * @param Whitelist $callbackUrlWhitelist
     * @param array $requestData
     */
    public function testInvalidRequest(Whitelist $callbackUrlWhitelist, array $requestData)
    {
        $controller = new RequestController(
            $callbackUrlWhitelist,
            \Mockery::mock(CachedResourceManager::class),
            \Mockery::mock(CachedResourceValidator::class),
            \Mockery::mock(CallbackFactory::class),
            \Mockery::mock(CallbackManager::class),
            \Mockery::mock(RetrieveResourceJobManager::class)
        );

        $request = new Request([], $requestData);
        $response = $controller->requestAction($request);

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
