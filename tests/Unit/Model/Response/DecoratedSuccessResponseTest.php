<?php

namespace App\Tests\Unit\Model\Response;

use App\Entity\CachedResource;
use App\Model\Response\DecoratedSuccessResponse;
use App\Model\Response\SuccessResponse;
use webignition\HttpHeaders\Headers;

class DecoratedSuccessResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param SuccessResponse $successResponse
     * @param CachedResource $cachedResource
     * @param string $expectedJson
     */
    public function testJsonSerialize(
        SuccessResponse $successResponse,
        CachedResource $cachedResource,
        string $expectedJson
    ) {
        $decoratedSuccessResponse = new DecoratedSuccessResponse($successResponse, $cachedResource);

        $this->assertEquals($expectedJson, json_encode($decoratedSuccessResponse));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'empty headers, empty content' => [
                'successResponse' => new SuccessResponse('request_hash_1'),
                'cachedResource' => $this->createCachedResource(new Headers(), ''),
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_1',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                    'headers' => [],
                    'content' => '',
                ]),
            ],
            'has headers, has content' => [
                'successResponse' => new SuccessResponse('request_hash_2'),
                'cachedResource' => $this->createCachedResource(new Headers([
                    'content-type' => 'text/plain; charset=utf-8',
                ]), 'text body content'),
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_2',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                    'headers' => [
                        'content-type' => ['text/plain; charset=utf-8'],
                    ],
                    'content' => 'text body content',
                ]),
            ],
        ];
    }

    public function testGetRequestId()
    {
        $requestHash = 'request-hash';

        $response = new DecoratedSuccessResponse(
            new SuccessResponse($requestHash),
            \Mockery::mock(CachedResource::class)
        );

        $this->assertEquals($requestHash, $response->getRequestId());
    }

    private function createCachedResource(Headers $headers, string $body): CachedResource
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $body);
        rewind($stream);

        $resource = \Mockery::mock(CachedResource::class);

        $resource
            ->shouldReceive('getHeaders')
            ->andReturn($headers);

        $resource
            ->shouldReceive('getBody')
            ->andReturn($stream);

        return $resource;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
