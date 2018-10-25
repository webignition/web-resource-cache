<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\Response\UnknownFailureResponse;

class UnknownFailureResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param string $requestHash
     * @param string $expectedJson
     */
    public function testJsonSerialize(string $requestHash, string $expectedJson)
    {
        $response = new UnknownFailureResponse($requestHash);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'default' => [
                'requestHash' => 'request_hash',
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => UnknownFailureResponse::TYPE_UNKNOWN,
                ]),
            ],
        ];
    }

    public function testGetRequestId()
    {
        $requestHash = 'request-hash';

        $response = new UnknownFailureResponse($requestHash);

        $this->assertEquals($requestHash, $response->getRequestId());
    }
}
