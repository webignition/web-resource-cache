<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\RequestIdentifier;
use App\Model\Response\UnknownFailureResponse;

class UnknownFailureResponseTest extends AbstractResponseTest
{
    /**
     * @dataProvider toScalarArrayDataProvider
     *
     * @param RequestIdentifier $requestIdentifier
     * @param array $expectedArray
     */
    public function testToScalarArray(RequestIdentifier $requestIdentifier, array $expectedArray)
    {
        $response = new UnknownFailureResponse($requestIdentifier);

        $this->assertEquals($expectedArray, $response->toScalarArray());
    }

    public function toScalarArrayDataProvider(): array
    {
        return [
            'default' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash'),
                'expectedArray' => [
                    'request_id' => 'request_identifier_hash',
                    'status' => 'failed',
                    'failure_type' => UnknownFailureResponse::TYPE_UNKNOWN,
                ],
            ],
        ];
    }

    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param RequestIdentifier $requestIdentifier
     * @param string $expectedJson
     */
    public function testJsonSerialize(RequestIdentifier $requestIdentifier, string $expectedJson)
    {
        $response = new UnknownFailureResponse($requestIdentifier);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'default' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash'),
                'expectedJson' => json_encode([
                    'request_id' => 'request_identifier_hash',
                    'status' => 'failed',
                    'failure_type' => UnknownFailureResponse::TYPE_UNKNOWN,
                ]),
            ],
        ];
    }
}
