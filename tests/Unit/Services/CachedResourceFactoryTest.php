<?php

namespace App\Tests\Unit\Services;

use App\Services\CachedResourceFactory;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use webignition\HttpHeaders\Headers;

class CachedResourceFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CachedResourceFactory
     */
    private $cachedResourceFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->cachedResourceFactory = new CachedResourceFactory();
    }

    /**
     * @dataProvider createFromPsr7ResponseNotSuccessResponseDataProvider
     *
     * @param HttpResponseInterface $response
     */
    public function testCreateFromPsr7ResponseNotSuccessResponse(HttpResponseInterface $response)
    {
        $this->assertNull($this->cachedResourceFactory->createFromPsr7Response($response));
    }

    public function createFromPsr7ResponseNotSuccessResponseDataProvider(): array
    {
        return [
            '301' => [
                'response' => new Response(301),
            ],
            '404' => [
                'response' => new Response(404),
            ],
        ];
    }

    /**
     * @dataProvider createFromPsr7ResponseSuccessResponseDataProvider
     *
     * @param HttpResponseInterface $response
     * @param array $expectedCachedResourceHeaders
     * @param string $expectedCachedResourceBody
     */
    public function testCreateFromPsr7ResponseSuccessResponse(
        HttpResponseInterface $response,
        array $expectedCachedResourceHeaders,
        string $expectedCachedResourceBody
    ) {
        $cachedResource = $this->cachedResourceFactory->createFromPsr7Response($response);

        $cachedResourceHeaders = $cachedResource->getHeaders();

        $this->assertInstanceOf(Headers::class, $cachedResourceHeaders);
        $this->assertSame($expectedCachedResourceHeaders, $cachedResourceHeaders->toArray());
        $this->assertSame($expectedCachedResourceBody, $cachedResource->getBody());
    }

    public function createFromPsr7ResponseSuccessResponseDataProvider(): array
    {
        return [
            'empty headers, empty body' => [
                'response' => new Response(200, [], ''),
                'expectedCachedResourceHeaders' => [],
                'expectedCachedResourceBody' => '',
            ],
            'has headers, has body' => [
                'response' => new Response(200, ['content-type' => 'text/plain'], 'response body'),
                'expectedCachedResourceHeaders' => [
                    'content-type' => [
                        'text/plain',
                    ],
                ],
                'expectedCachedResourceBody' => 'response body',
            ],
        ];
    }
}
