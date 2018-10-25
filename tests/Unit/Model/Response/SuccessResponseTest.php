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

    /**
     * @dataProvider fromJsonInvalidDataDataProvider
     *
     * @param string $json
     */
    public function testFromJsonInvalidData(string $json)
    {
        $this->assertNull(SuccessResponse::fromJson($json));
    }

    public function fromJsonInvalidDataDataProvider(): array
    {
        return [
            'empty' => [
                'json' => '',
            ],
            'not an array' => [
                'json' => json_encode('foo'),
            ],
            'missing request_id' => [
                'json' => json_encode([
                    'foo' => 'bar',
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
}
