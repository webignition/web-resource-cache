<?php

namespace App\Tests\Unit\Services;

use App\Entity\RetrieveRequest;
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
    public function testCreateResponseNotSuccessResponse(HttpResponseInterface $response)
    {
        $this->assertNull($this->cachedResourceFactory->create(new RetrieveRequest(), $response));
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
     * @dataProvider createSuccessDataProvider
     *
     * @param HttpResponseInterface $response
     * @param array $expectedCachedResourceHeaders
     * @param string $expectedCachedResourceBody
     */
    public function testCreateSuccess(
        HttpResponseInterface $response,
        array $expectedCachedResourceHeaders,
        string $expectedCachedResourceBody
    ) {
        $retrieveRequest = new RetrieveRequest();
        $retrieveRequest->setUrl('http://example.com/');
        $retrieveRequest->setHash('request_hash');

        $cachedResource = $this->cachedResourceFactory->create($retrieveRequest, $response);

        $cachedResourceHeaders = $cachedResource->getHeaders();

        $this->assertInstanceOf(Headers::class, $cachedResourceHeaders);
        $this->assertSame($expectedCachedResourceHeaders, $cachedResourceHeaders->toArray());
        $this->assertSame($expectedCachedResourceBody, $cachedResource->getBody());
        $this->assertSame($retrieveRequest->getUrl(), $cachedResource->getUrl());
        $this->assertSame($retrieveRequest->getHash(), $cachedResource->getRequestHash());
    }

    public function createSuccessDataProvider(): array
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
