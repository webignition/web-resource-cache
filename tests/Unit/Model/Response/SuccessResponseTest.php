<?php

namespace App\Tests\Unit\Model\Response;

use App\Entity\Resource;
use App\Model\Headers;
use App\Model\RequestIdentifier;
use App\Model\Response\SuccessResponse;

class SuccessResponseTest extends AbstractResponseTest
{
    /**
     * @dataProvider toScalarArrayDataProvider
     *
     * @param RequestIdentifier $requestIdentifier
     * @param Resource $resource
     * @param array $expectedArray
     */
    public function testToScalarArray(RequestIdentifier $requestIdentifier, Resource $resource, array $expectedArray)
    {
        $successResponse = new SuccessResponse($requestIdentifier, $resource);

        $this->assertEquals($expectedArray, $successResponse->toScalarArray());
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

    private function createResource(Headers $headers, string $body): Resource
    {
        $resource = \Mockery::mock(Resource::class);

        $resource
            ->shouldReceive('getHeaders')
            ->andReturn($headers);

        $resource
            ->shouldReceive('getBody')
            ->andReturn($body);

        return $resource;
    }
}
