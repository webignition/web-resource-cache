<?php

namespace App\Tests\Unit\Model\Response;

use App\Entity\CachedResource;
use App\Model\Response\SuccessResponse;
use webignition\HttpHeaders\Headers;

class SuccessResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider toScalarArrayDataProvider
     *
     * @param string $requestHash
     * @param CachedResource $resource
     * @param array $expectedArray
     */
    public function testToScalarArray(
        string $requestHash,
        CachedResource $resource,
        array $expectedArray
    ) {
        $response = new SuccessResponse($requestHash, $resource);

        $this->assertEquals($expectedArray, $response->toScalarArray());
    }

    public function toScalarArrayDataProvider(): array
    {
        return [
            'empty headers, empty content' => [
                'requestHash' => 'request_hash_1',
                'resource' => $this->createCachedResource(new Headers(), ''),
                'expectedArray' => [
                    'request_id' => 'request_hash_1',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                ],
            ],
            'has headers, has content' => [
                'requestHash' => 'request_hash_2',
                'resource' => $this->createCachedResource(new Headers([
                    'content-type' => 'text/plain; charset=utf-8',
                ]), 'text body content'),
                'expectedArray' => [
                    'request_id' => 'request_hash_2',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                ],
            ],
        ];
    }

    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param string $requestHash
     * @param CachedResource $resource
     * @param string $expectedJson
     */
    public function testJsonSerialize(
        string $requestHash,
        CachedResource $resource,
        string $expectedJson
    ) {
        $response = new SuccessResponse($requestHash, $resource);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'empty headers, empty content' => [
                'requestHash' => 'request_hash_1',
                'resource' => $this->createCachedResource(new Headers(), ''),
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_1',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                    'headers' => [],
                    'content' => '',
                ]),
            ],
            'has headers, has content' => [
                'requestHash' => 'request_hash_2',
                'resource' => $this->createCachedResource(new Headers([
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
