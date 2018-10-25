<?php

namespace App\Tests\Unit\Services;

use App\Model\Response\KnownFailureResponse;
use App\Model\Response\RebuildableDecoratedResponse;
use App\Model\Response\SuccessResponse;
use App\Model\Response\UnknownFailureResponse;
use App\Services\ResponseFactory;

class ResponseFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->responseFactory = new ResponseFactory();
    }

    /**
     * @dataProvider createFromJsonInvalidDataDataProvider
     *
     * @param string $json
     */
    public function testCreateFromJsonInvalidData(string $json)
    {
        $this->assertNull($this->responseFactory->createFromJson($json));
    }

    public function createFromJsonInvalidDataDataProvider(): array
    {
        return [
            'empty json' => [
                'json' => '',
            ],
            'whitespace' => [
                'json' => '  ',
            ],
            'not an array' => [
                'json' => json_encode(true),
            ],
            'missing request_id' => [
                'json' => json_encode([
                    'foo' => 'bar',
                ]),
            ],
            'missing class' => [
                'json' => json_encode([
                    'request_id' => 'request_hash',
                ]),
            ],
            'invalid class' => [
                'json' => json_encode([
                    'request_id' => 'request_hash',
                    'class' => 'Foo',
                ]),
            ],
            'class not implements ResponseInterface' => [
                'json' => json_encode([
                    'request_id' => 'request_hash',
                    'class' => get_class($this),
                ]),
            ],
            'success response missing request_id' => [
                'json' => json_encode([
                    'class' => SuccessResponse::class,
                ]),
            ],
            'unknown failure response missing request_id' => [
                'json' => json_encode([
                    'class' => UnknownFailureResponse::class,
                ]),
            ],
            'known failure response missing request_id' => [
                'json' => json_encode([
                    'class' => KnownFailureResponse::class,
                ]),
            ],
            'known failure response missing failure_type' => [
                'json' => json_encode([
                    'class' => KnownFailureResponse::class,
                    'request_id' => 'request_hash',
                ]),
            ],
            'known failure response missing status_code' => [
                'json' => json_encode([
                    'class' => KnownFailureResponse::class,
                    'request_id' => 'request_hash',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                ]),
            ],
        ];
    }

    /**
     * @dataProvider createFromJsonSuccessDataProvider
     *
     * @param string $responseJson
     * @param string $expectedClass
     * @param array $expectedResponseData
     */
    public function testCreateFromJsonSuccess(string $responseJson, string $expectedClass, array $expectedResponseData)
    {
        $response = $this->responseFactory->createFromJson($responseJson);

        $this->assertInstanceOf($expectedClass, $response);
        $this->assertEquals(json_encode($expectedResponseData), json_encode($response));
    }

    public function createFromJsonSuccessDataProvider(): array
    {
        return [
            'success response' => [
                'json' => json_encode(new RebuildableDecoratedResponse(
                    new SuccessResponse('request_hash')
                )),
                'expectedClass' => SuccessResponse::class,
                'expectedResponseData' => [
                    'request_id' => 'request_hash',
                    'status' => SuccessResponse::STATUS_SUCCESS,
                ],
            ],
            'unknown failure response' => [
                'json' => json_encode(new RebuildableDecoratedResponse(
                    new UnknownFailureResponse('request_hash')
                )),
                'expectedClass' => UnknownFailureResponse::class,
                'expectedResponseData' => [
                    'request_id' => 'request_hash',
                    'status' => UnknownFailureResponse::STATUS_FAILED,
                    'failure_type' => UnknownFailureResponse::TYPE_UNKNOWN,
                ],
            ],
            'http 404 failure response' => [
                'json' => json_encode(new RebuildableDecoratedResponse(
                    new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_HTTP, 404)
                )),
                'expectedClass' => KnownFailureResponse::class,
                'expectedResponseData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                    'status_code' => 404,
                ],
            ],
            'curl 28 failure response' => [
                'json' => json_encode(new RebuildableDecoratedResponse(
                    new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_CONNECTION, 28)
                )),
                'expectedClass' => KnownFailureResponse::class,
                'expectedResponseData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => KnownFailureResponse::TYPE_CONNECTION,
                    'status_code' => 28,
                ],
            ],
        ];
    }
}
