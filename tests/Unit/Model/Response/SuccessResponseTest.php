<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\Response\SuccessResponse;

class SuccessResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param string $requestHash
     * @param string $expectedJson
     */
    public function testJsonSerialize(string $requestHash, string $expectedJson)
    {
        $response = new SuccessResponse($requestHash);

        $this->assertEquals($expectedJson, json_encode($response));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'default' => [
                'requestHash' => 'request_hash_1',
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash_1',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                ]),
            ],
        ];
    }

    public function testFromJsonValidData()
    {
        $requestHash = 'request_hash';

        $json = json_encode([
            'request_id' => $requestHash,
        ]);

        $response = SuccessResponse::fromJson($json);

        $this->assertInstanceOf(SuccessResponse::class, $response);
        $this->assertEquals(
            json_encode([
                'request_id' => $requestHash,
                'status' => SuccessResponse::STATUS_SUCCESS,
            ]),
            json_encode($response)
        );
    }

    public function testGetRequestId()
    {
        $requestHash = 'request-hash';

        $response = new SuccessResponse($requestHash);

        $this->assertEquals($requestHash, $response->getRequestId());
    }
}
