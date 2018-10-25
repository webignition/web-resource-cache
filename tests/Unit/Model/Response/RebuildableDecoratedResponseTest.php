<?php

namespace App\Tests\Unit\Model\Response;

use App\Model\Response\AbstractResponse;
use App\Model\Response\KnownFailureResponse;
use App\Model\Response\RebuildableDecoratedResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;

class RebuildableDecoratedResponseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param AbstractResponse $wrappedResponse
     * @param string $expectedJson
     */
    public function testJsonSerialize(
        AbstractResponse $wrappedResponse,
        string $expectedJson
    ) {
        $decoratedSuccessResponse = new RebuildableDecoratedResponse($wrappedResponse);

        $this->assertEquals($expectedJson, json_encode($decoratedSuccessResponse));
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'success response' => [
                'wrappedResponse' => new SuccessResponse('request_hash'),
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                    'class' => SuccessResponse::class,
                ]),
            ],
            'known failure response' => [
                'wrappedResponse' => new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_HTTP, 404),
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                    'class' => KnownFailureResponse::class,
                ]),
            ],
            'unknown failure response' => [
                'wrappedResponse' => new UnknownFailureResponse('request_hash'),
                'expectedJson' => json_encode([
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => UnknownFailureResponse::TYPE_UNKNOWN,
                    'class' => UnknownFailureResponse::class,
                ]),
            ],
        ];
    }
}
