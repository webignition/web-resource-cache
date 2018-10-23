<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\Response\UnknownFailureResponse;

class UnknownFailureResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider toScalarArrayDataProvider
     *
     * @param string $requestHash
     * @param array $expectedArray
     */
    public function testToScalarArray(string $requestHash, array $expectedArray)
    {
        $response = new UnknownFailureResponse($requestHash);

        $this->assertEquals($expectedArray, $response->toScalarArray());
    }

    public function toScalarArrayDataProvider(): array
    {
        return [
            'default' => [
                'requestHash' => 'request_hash',
                'expectedArray' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => UnknownFailureResponse::TYPE_UNKNOWN,
                ],
            ],
        ];
    }

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
}
