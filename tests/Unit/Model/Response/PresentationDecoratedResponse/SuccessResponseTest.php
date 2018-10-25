<?php

namespace App\Tests\Unit\Model\Response\PresentationDecoratedResponse;

use App\Entity\CachedResource;
use App\Model\Response\PresentationDecoratedResponse\SuccessResponse;
use App\Model\Response\SuccessResponse as BaseSuccessResponse;
use webignition\HttpHeaders\Headers;

class SuccessResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param BaseSuccessResponse $successResponse
     * @param CachedResource $cachedResource
     * @param string $expectedJson
     */
    public function testJsonSerialize(
        BaseSuccessResponse $successResponse,
        CachedResource $cachedResource,
        string $expectedJson
    ) {
        $decoratedSuccessResponse = new SuccessResponse($successResponse, $cachedResource);

        $this->assertEquals($expectedJson, json_encode($decoratedSuccessResponse));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'empty headers, empty content' => [
                'successResponse' => new BaseSuccessResponse('request_hash_1'),
                'cachedResource' => $this->createCachedResource(new Headers(), ''),
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_1',
                    'status' => BaseSuccessResponse::STATUS_SUCCESS,
                    'headers' => [],
                    'content' => '',
                ]),
            ],
            'has headers, has content' => [
                'successResponse' => new BaseSuccessResponse('request_hash_2'),
                'cachedResource' => $this->createCachedResource(new Headers([
                    'content-type' => 'text/plain; charset=utf-8',
                ]), 'text body content'),
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_2',
                    'status' => BaseSuccessResponse::STATUS_SUCCESS,
                    'headers' => [
                        'content-type' => ['text/plain; charset=utf-8'],
                    ],
                    'content' => 'text body content',
                ]),
            ],
        ];
    }

    private function createCachedResource(Headers $headers, string $body): CachedResource
    {
        $resource = \Mockery::mock(CachedResource::class);

        $resource
            ->shouldReceive('getHeaders')
            ->andReturn($headers);

        $resource
            ->shouldReceive('getBody')
            ->andReturn($body);

        return $resource;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
