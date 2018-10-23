<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\Response\ConnectionFailureResponse;

class ConnectionFailureResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider toScalarArrayDataProvider
     *
     * @param string $requestHash
     * @param int $statusCode
     * @param array $expectedArray
     */
    public function testToScalarArray(string $requestHash, int $statusCode, array $expectedArray)
    {
        $response = new ConnectionFailureResponse($requestHash, $statusCode);

        $this->assertEquals($expectedArray, $response->toScalarArray());
    }

    public function toScalarArrayDataProvider(): array
    {
        return [
            'curl 6' => [
                'requestHash' => 'request_hash_1',
                'statusCode' => 6,
                'expectedArray' => [
                    'request_id' => 'request_hash_1',
                    'status' => 'failed',
                    'status_code' => 6,
                    'failure_type' => ConnectionFailureResponse::TYPE_CONNECTION,
                ],
            ],
            'curl 28' => [
                'requestHash' => 'request_hash_2',
                'statusCode' => 28,
                'expectedArray' => [
                    'request_id' => 'request_hash_2',
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
     * @param string $requestHash
     * @param int $statusCode
     * @param string $expectedJson
     */
    public function testJsonSerialize(string $requestHash, int $statusCode, string $expectedJson)
    {
        $response = new ConnectionFailureResponse($requestHash, $statusCode);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'curl 6' => [
                'requestHash' => 'request_hash_1',
                'statusCode' => 6,
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_1',
                    'status' => 'failed',
                    'failure_type' => ConnectionFailureResponse::TYPE_CONNECTION,
                    'status_code' => 6,
                ]),
            ],
            'curl 28' => [
                'requestHash' => 'request_hash_2',
                'statusCode' => 28,
                'expectedArray' => json_encode([
                    'request_id' => 'request_hash_2',
                    'status' => 'failed',
                    'failure_type' => ConnectionFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                ]),
            ],
        ];
    }
}
