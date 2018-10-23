<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\Response\HttpFailureResponse;

class HttpFailureResponseTest extends \PHPUnit\Framework\TestCase
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
        $response = new HttpFailureResponse($requestHash, $statusCode);

        $this->assertEquals($expectedArray, $response->toScalarArray());
    }

    public function toScalarArrayDataProvider(): array
    {
        return [
            'http 404' => [
                'requestHash' => 'request_hash_1',
                'statusCode' => 404,
                'expectedArray' => [
                    'request_id' => 'request_hash_1',
                    'status' => 'failed',
                    'failure_type' => HttpFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ],
            ],
            'http 500' => [
                'requestHash' => 'request_hash_2',
                'statusCode' => 500,
                'expectedArray' => [
                    'request_id' => 'request_hash_2',
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
     * @param string $requestHash
     * @param int $statusCode
     * @param string $expectedJson
     */
    public function testJsonSerialize(string $requestHash, int $statusCode, string $expectedJson)
    {
        $response = new HttpFailureResponse($requestHash, $statusCode);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'http 404' => [
                'requestHash' => 'request_hash_1',
                'statusCode' => 404,
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_1',
                    'status' => 'failed',
                    'failure_type' => HttpFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ]),
            ],
            'http 500' => [
                'requestHash' => 'request_hash_2',
                'statusCode' => 500,
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_2',
                    'status' => 'failed',
                    'failure_type' => HttpFailureResponse::TYPE_HTTP,
                    'status_code' => 500,
                ]),
            ],
        ];
    }
}
