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
        $successResponse = new HttpFailureResponse($requestIdentifier, $statusCode);

        $this->assertEquals($expectedArray, $successResponse->toScalarArray());
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
                    'status_code' => 404,
                    'failure_type' => HttpFailureResponse::TYPE_HTTP,
                ],
            ],
            'http 500' => [
                'requestIdentifier' => $this->createRequestIdentifier('request_identifier_hash_2'),
                'statusCode' => 500,
                'expectedArray' => [
                    'request_id' => 'request_identifier_hash_2',
                    'status' => 'failed',
                    'status_code' => 500,
                    'failure_type' => HttpFailureResponse::TYPE_HTTP,
                ],
            ],
        ];
    }
}
