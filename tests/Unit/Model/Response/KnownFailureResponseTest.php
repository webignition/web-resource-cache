<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Model\Response;

use App\Model\Response\KnownFailureResponse;

class KnownFailureResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     */
    public function testJsonSerialize(
        string $requestHash,
        string $type,
        int $statusCode,
        array $context,
        string $expectedJson
    ) {
        $response = new KnownFailureResponse($requestHash, $type, $statusCode, $context);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'curl 6' => [
                'requestHash' => 'request_hash_1',
                'type' => KnownFailureResponse::TYPE_CONNECTION,
                'statusCode' => 6,
                'context' => [],
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_1',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 6,
                    'context' => [],
                ]),
            ],
            'curl 28' => [
                'requestHash' => 'request_hash_2',
                'type' => KnownFailureResponse::TYPE_CONNECTION,
                'statusCode' => 28,
                'context' => [],
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_2',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                    'context' => [],
                ]),
            ],
            'http 404' => [
                'requestHash' => 'request_hash_3',
                'type' => KnownFailureResponse::TYPE_HTTP,
                'statusCode' => 404,
                'context' => [],
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_3',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                    'context' => [],
                ]),
            ],
            'http 500' => [
                'requestHash' => 'request_hash_4',
                'type' => KnownFailureResponse::TYPE_HTTP,
                'statusCode' => 500,
                'context' => [],
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_4',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 500,
                    'context' => [],
                ]),
            ],
            'http 301 with too many redirects context' => [
                'requestHash' => 'request_hash_5',
                'type' => KnownFailureResponse::TYPE_HTTP,
                'statusCode' => 301,
                'context' => [
                    'too_many_redirects' => true,
                    'is_redirect_loop' => false,
                    'history' => [
                        'http://example.com/1',
                        'http://example.com/2',
                        'http://example.com/3',
                        'http://example.com/4',
                        'http://example.com/5',
                        'http://example.com/6',
                    ],
                ],
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_5',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 301,
                    'context' => [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => false,
                        'history' => [
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                            'http://example.com/4',
                            'http://example.com/5',
                            'http://example.com/6',
                        ],
                    ],
                ]),
            ],
            'http 301 with redirect loop context' => [
                'requestHash' => 'request_hash_5',
                'type' => KnownFailureResponse::TYPE_HTTP,
                'statusCode' => 301,
                'context' => [
                    'too_many_redirects' => true,
                    'is_redirect_loop' => true,
                    'history' => [
                        'http://example.com/1',
                        'http://example.com/2',
                        'http://example.com/3',
                        'http://example.com/1',
                        'http://example.com/2',
                        'http://example.com/3',
                    ],
                ],
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_5',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 301,
                    'context' => [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => true,
                        'history' => [
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                        ],
                    ],
                ]),
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
