<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\RequestIdentifier;
use App\Model\Response\ConnectionFailureResponse;

class ConnectionFailureResponseTest extends AbstractResponseTest
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
        $response = new ConnectionFailureResponse($requestIdentifier, $statusCode);

        $this->assertEquals($expectedArray, $response->toScalarArray());
    }

    public function toScalarArrayDataProvider(): array
    {
        return [
            'curl 6' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_1'),
                'statusCode' => 6,
                'expectedArray' => [
                    'request_id' => 'request_identifier_hash_1',
                    'status' => 'failed',
                    'status_code' => 6,
                    'failure_type' => ConnectionFailureResponse::TYPE_CONNECTION,
                ],
            ],
            'curl 28' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_2'),
                'statusCode' => 28,
                'expectedArray' => [
                    'request_id' => 'request_identifier_hash_2',
                    'status' => 'failed',
                    'status_code' => 28,
                    'failure_type' => ConnectionFailureResponse::TYPE_CONNECTION,
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
        $response = new ConnectionFailureResponse($requestIdentifier, $statusCode);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'curl 6' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_1'),
                'statusCode' => 6,
                'expectedJson' => json_encode([
                    'request_id' => 'request_identifier_hash_1',
                    'status' => 'failed',
                    'failure_type' => ConnectionFailureResponse::TYPE_CONNECTION,
                    'status_code' => 6,
                ]),
            ],
            'curl 28' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_2'),
                'statusCode' => 28,
                'expectedArray' => json_encode([
                    'request_id' => 'request_identifier_hash_2',
                    'status' => 'failed',
                    'failure_type' => ConnectionFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                ]),
            ],
        ];
    }
}
