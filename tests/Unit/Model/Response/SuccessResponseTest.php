<?php

namespace App\Tests\Unit\Model\Response;

use App\Entity\CachedResource;
use App\Model\Headers;
use App\Model\RequestIdentifier;
use App\Model\Response\SuccessResponse;

class SuccessResponseTest extends AbstractResponseTest
{
    /**
     * @dataProvider toScalarArrayDataProvider
     *
     * @param RequestIdentifier $requestIdentifier
     * @param CachedResource $resource
     * @param array $expectedArray
     */
    public function testToScalarArray(RequestIdentifier $requestIdentifier, CachedResource $resource, array $expectedArray)
    {
        $response = new SuccessResponse($requestIdentifier, $resource);

        $this->assertEquals($expectedArray, $response->toScalarArray());
    }

    public function toScalarArrayDataProvider(): array
    {
        return [
            'empty headers, empty content' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_1'),
                'resource' => $this->createResource(new Headers(), ''),
                'expectedArray' => [
                    'request_id' => 'request_identifier_hash_1',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                ],
            ],
            'has headers, has content' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_2'),
                'resource' => $this->createResource(new Headers([
                    'content-type' => 'text/plain; charset=utf-8',
                ]), 'text body content'),
                'expectedArray' => [
                    'request_id' => 'request_identifier_hash_2',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                ],
            ],
        ];
    }

    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param RequestIdentifier $requestIdentifier
     * @param CachedResource $resource
     * @param string $expectedJson
     */
    public function testJsonSerialize(RequestIdentifier $requestIdentifier, CachedResource $resource, string $expectedJson)
    {
        $response = new SuccessResponse($requestIdentifier, $resource);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'empty headers, empty content' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_1'),
                'resource' => $this->createResource(new Headers(), ''),
                'expectedJson' => json_encode([
                    'request_id' => 'request_identifier_hash_1',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                    'headers' => [],
                    'content' => '',
                ]),
            ],
            'has headers, has content' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_2'),
                'resource' => $this->createResource(new Headers([
                    'content-type' => 'text/plain; charset=utf-8',
                ]), 'text body content'),
                'expectedJson' => json_encode([
                    'request_id' => 'request_identifier_hash_2',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                    'headers' => [
                        'content-type' => 'text/plain; charset=utf-8',
                    ],
                    'content' => 'text body content',
                ]),
            ],
        ];
    }

    private function createResource(Headers $headers, string $body): CachedResource
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
}
