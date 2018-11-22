<?php

namespace App\Tests\Unit\Services;

use App\Entity\CachedResource;
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
        $this->assertNull($this->cachedResourceFactory->create('request_hash', 'http://example.com/', $response));
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
        $requestHash = 'request_hash';
        $url = 'http://example.com/';

        $cachedResource = $this->cachedResourceFactory->create($requestHash, $url, $response);

        $cachedResourceHeaders = $cachedResource->getHeaders();

        $this->assertInstanceOf(Headers::class, $cachedResourceHeaders);
        $this->assertSame($expectedCachedResourceHeaders, $cachedResourceHeaders->toArray());
        $this->assertSame($expectedCachedResourceBody, stream_get_contents($cachedResource->getBody()));
        $this->assertSame($url, $cachedResource->getUrl());
        $this->assertSame($requestHash, $cachedResource->getRequestHash());
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

    public function testUpdateResponseNotSuccessResponse()
    {
        $response = new Response(200, ['foo' => 'bar'], 'response content');

        $cachedResource = $this->cachedResourceFactory->create('request_hash', 'http://example.com/', $response);

        $this->assertSame($cachedResource->getHeaders()->toArray(), $response->getHeaders());
        $this->assertSame(stream_get_contents($cachedResource->getBody()), (string) $response->getBody());

        $this->cachedResourceFactory->updateResponse($cachedResource, new Response(404));

        $this->assertSame($cachedResource->getHeaders()->toArray(), $response->getHeaders());
        $this->assertSame(stream_get_contents($cachedResource->getBody()), (string) $response->getBody());
    }

    /**
     * @dataProvider updatedResponseSuccessDataProvider
     *
     * @param CachedResource $cachedResource
     * @param HttpResponseInterface $response
     * @param array $expectedCachedResourceHeaders
     * @param string $expectedCachedResourceBody
     */
    public function testUpdateResponseSuccess(
        CachedResource $cachedResource,
        HttpResponseInterface $response,
        array $expectedCachedResourceHeaders,
        string $expectedCachedResourceBody
    ) {
        $this->cachedResourceFactory->updateResponse($cachedResource, $response);

        $this->assertSame($expectedCachedResourceHeaders, $cachedResource->getHeaders()->toArray());
        $this->assertSame($expectedCachedResourceBody, stream_get_contents($cachedResource->getBody()));
    }

    public function updatedResponseSuccessDataProvider(): array
    {
        return [
            'initial: no headers, no body; updated: no headers, no body' => [
                'cachedResource' => $this->createCachedResource(new Headers(), ''),
                'response' => new Response(),
                'expectedCachedResourceHeaders' => [],
                'expectedCachedResourceBody' => '',
            ],
            'initial: no headers, no body; updated: has headers, has body' => [
                'cachedResource' => $this->createCachedResource(new Headers(), ''),
                'response' => new Response(200, ['foo' => 'bar'], 'response content'),
                'expectedCachedResourceHeaders' => ['foo' => ['bar']],
                'expectedCachedResourceBody' => 'response content',
            ],
            'initial: has headers, has body; updated: no headers, no body' => [
                'cachedResource' => $this->createCachedResource(new Headers(['foo' => 'bar']), 'response content'),
                'response' => new Response(),
                'expectedCachedResourceHeaders' => [],
                'expectedCachedResourceBody' => '',
            ],
            'initial: has headers, has body; updated: has headers, has body' => [
                'cachedResource' => $this->createCachedResource(new Headers(['foo' => 'bar']), 'response content'),
                'response' => new Response(200, ['fizz' => 'buzz'], 'updated response content'),
                'expectedCachedResourceHeaders' => ['fizz' => ['buzz']],
                'expectedCachedResourceBody' => 'updated response content',
            ],
        ];
    }

    private function createCachedResource(Headers $headers, string $body): CachedResource
    {
        $cachedResource = new CachedResource();

        $cachedResource->setHeaders($headers);
        $cachedResource->setBody($body);

        return $cachedResource;
    }
}
