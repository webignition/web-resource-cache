<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\RequestIdentifier;
use App\Model\Response\HttpFailureResponse;

class HttpFailureResponseTest extends AbstractResponseTest
{
    /**
     * @dataProvider toScalarArrayDataProvider
     *
     * @param RequestIdentifier $requestIdentifier
     * @param int $statusCode
     * @param array $expectedArray
     */
    public function testToScalarArray(RequestIdentifier $requestIdentifier, int $statusCode, array $expectedArray)
    {
        $response = new HttpFailureResponse($requestIdentifier, $statusCode);

        $this->assertEquals($expectedArray, $response->toScalarArray());
    }

    public function toScalarArrayDataProvider(): array
    {
        return [
            'http 404' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_1'),
                'statusCode' => 404,
                'expectedArray' => [
                    'request_id' => 'request_identifier_hash_1',
                    'status' => 'failed',
                    'failure_type' => HttpFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ],
            ],
            'http 500' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_2'),
                'statusCode' => 500,
                'expectedArray' => [
                    'request_id' => 'request_identifier_hash_2',
                    'status' => 'failed',
                    'failure_type' => HttpFailureResponse::TYPE_HTTP,
                    'status_code' => 500,
                ],
            ],
        ];
    }

    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param RequestIdentifier $requestIdentifier
     * @param int $statusCode
     * @param string $expectedJson
     */
    public function testJsonSerialize(RequestIdentifier $requestIdentifier, int $statusCode, string $expectedJson)
    {
        $response = new HttpFailureResponse($requestIdentifier, $statusCode);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'http 404' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_1'),
                'statusCode' => 404,
                'expectedJson' => json_encode([
                    'request_id' => 'request_identifier_hash_1',
                    'status' => 'failed',
                    'failure_type' => HttpFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ]),
            ],
            'http 500' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_2'),
                'statusCode' => 500,
                'expectedJson' => json_encode([
                    'request_id' => 'request_identifier_hash_2',
                    'status' => 'failed',
                    'failure_type' => HttpFailureResponse::TYPE_HTTP,
                    'status_code' => 500,
                ]),
            ],
        ];
    }
}
