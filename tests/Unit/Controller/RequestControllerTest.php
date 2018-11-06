<?php

namespace App\Tests\Unit\Controller;

use App\Controller\RequestController;
use App\Services\CachedResourceManager;
use App\Services\CachedResourceValidator;
use App\Services\CallbackFactory;
use App\Services\CallbackManager;
use App\Services\CallbackUrlValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

class RequestControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider invalidRequestDataProvider
     *
     * @param CallbackUrlValidator $callbackUrlValidator
     * @param array $requestData
     */
    public function testInvalidRequest(CallbackUrlValidator $callbackUrlValidator, array $requestData)
    {
        $controller = new RequestController(
            $callbackUrlValidator,
            \Mockery::mock(CachedResourceManager::class),
            \Mockery::mock(CachedResourceValidator::class),
            \Mockery::mock(CallbackFactory::class),
            \Mockery::mock(CallbackManager::class),
            \Mockery::mock(MessageBusInterface::class)
        );

        $request = new Request([], $requestData);
        $response = $controller->requestAction($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function invalidRequestDataProvider(): array
    {
        return [
            'empty request' => [
                'callbackUrlValidator' => new CallbackUrlValidator([]),
                'requestData' => [],
            ],
            'missing callback url' => [
                'callbackUrlValidator' => new CallbackUrlValidator([]),
                'requestData' => [
                    'uri' => 'http://example.com/',
                ],
            ],
            'non-allowed callback url (empty)' => [
                'callbackUrlValidator' => new CallbackUrlValidator([]),
                'requestData' => [
                    'uri' => 'http://example.com/',
                    'callback' => '',
                ],
            ],
            'non-allowed callback url (non-matching)' => [
                'callbackUrlValidator' => new CallbackUrlValidator([
                    'foo.example.com',
                ]),
                'requestData' => [
                    'uri' => 'http://example.com/',
                    'callback' => 'http://bar.example.com/callback',
                ],
            ],
        ];
    }
}
