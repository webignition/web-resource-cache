<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\Response\KnownFailureResponse;

class KnownFailureResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param string $requestHash
     * @param string $type
     * @param int $statusCode
     * @param string $expectedJson
     */
    public function testJsonSerialize(string $requestHash, string $type, int $statusCode, string $expectedJson)
    {
        $response = new KnownFailureResponse($requestHash, $type, $statusCode);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'curl 6' => [
                'requestHash' => 'request_hash_1',
                'type' => KnownFailureResponse::TYPE_CONNECTION,
                'statusCode' => 6,
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_1',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 6,
                ]),
            ],
            'curl 28' => [
                'requestHash' => 'request_hash_2',
                'type' => KnownFailureResponse::TYPE_CONNECTION,
                'statusCode' => 28,
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_2',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                ]),
            ],
            'http 404' => [
                'requestHash' => 'request_hash_3',
                'type' => KnownFailureResponse::TYPE_HTTP,
                'statusCode' => 404,
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_3',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ]),
            ],
            'http 500' => [
                'requestHash' => 'request_hash_4',
                'type' => KnownFailureResponse::TYPE_HTTP,
                'statusCode' => 500,
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_4',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 500,
                ]),
            ],
        ];
    }

    /**
     * @dataProvider fromJsonValidDataDataProvider
     *
     * @param string $json
     * @param array $expectedResponseData
     */
    public function testFromJsonValidData(string $json, array $expectedResponseData)
    {
        $response = KnownFailureResponse::fromJson($json);

        $this->assertInstanceOf(KnownFailureResponse::class, $response);
        $this->assertEquals(json_encode($expectedResponseData), json_encode($response));
    }

    public function fromJsonValidDataDataProvider(): array
    {
        return [
            'curl 6' => [
                'json' => json_encode([
                    'request_id' => 'request_hash_1',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 6,
                ]),
                'expectedResponseData' => [
                    'request_id' => 'request_hash_1',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 6,
                ],
            ],
            'curl 28' => [
                'json' => json_encode([
                    'request_id' => 'request_hash_2',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                ]),
                'expectedResponseData' => [
                    'request_id' => 'request_hash_2',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                ],
            ],
            'http 404' => [
                'json' => json_encode([
                    'request_id' => 'request_hash_3',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ]),
                'expectedResponseData' => [
                    'request_id' => 'request_hash_3',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ],
            ],
            'http 500' => [
                'json' => json_encode([
                    'request_id' => 'request_hash_4',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 6,
                ]),
                'expectedResponseData' => [
                    'request_id' => 'request_hash_4',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 6,
                ],
            ],
        ];
    }

    public function testGetRequestId()
    {
        $requestHash = 'request-hash';

        $response = new KnownFailureResponse($requestHash, KnownFailureResponse::TYPE_HTTP, 404);

        $this->assertEquals($requestHash, $response->getRequestId());
    }
}
