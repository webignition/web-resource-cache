<?php

namespace App\Tests\Unit\Services;

use App\Model\Response\KnownFailureResponse;
use App\Model\Response\RebuildableDecoratedResponse;
use App\Model\Response\ResponseInterface;
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
     * @dataProvider createFromArrayInvalidDataDataProvider
     *
     * @param array $data
     */
    public function testCreateFromArrayInvalidData(array $data)
    {
        $this->assertNull($this->responseFactory->createFromArray($data));
    }

    public function createFromArrayInvalidDataDataProvider(): array
    {
        return [
            'missing request_id' => [
                'data' => [
                    'foo' => 'bar',
                ],
            ],
            'missing class' => [
                'data' => [
                    'request_id' => 'request_hash',
                ],
            ],
            'invalid class' => [
                'data' => [
                    'request_id' => 'request_hash',
                    'class' => 'Foo',
                ],
            ],
            'class not implements ResponseInterface' => [
                'data' => [
                    'request_id' => 'request_hash',
                    'class' => get_class($this),
                ],
            ],
            'success response missing request_id' => [
                'data' => [
                    'class' => SuccessResponse::class,
                ],
            ],
            'unknown failure response missing request_id' => [
                'data' => [
                    'class' => UnknownFailureResponse::class,
                ],
            ],
            'known failure response missing request_id' => [
                'data' => [
                    'class' => KnownFailureResponse::class,
                ],
            ],
            'known failure response missing failure_type' => [
                'data' => [
                    'class' => KnownFailureResponse::class,
                    'request_id' => 'request_hash',
                ],
            ],
            'known failure response missing status_code' => [
                'data' => [
                    'class' => KnownFailureResponse::class,
                    'request_id' => 'request_hash',
                    'failure_type' => KnownFailureResponse::TYPE_HTTP,
                ],
            ],
        ];
    }

    /**
     * @dataProvider successDataProvider
     *
     * @param ResponseInterface $response
     * @param string $expectedClass
     * @param ResponseInterface $expectedResponse
     */
    public function testCreateFromArraySuccess(
        ResponseInterface $response,
        string $expectedClass,
        ResponseInterface $expectedResponse
    ) {
        $response = $this->responseFactory->createFromArray($response->jsonSerialize());

        $this->assertInstanceOf($expectedClass, $response);
        $this->assertEquals($expectedResponse, $response);
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
        ];
    }

    /**
     * @dataProvider successDataProvider
     *
     * @param ResponseInterface $response
     * @param string $expectedClass
     * @param ResponseInterface $expectedResponse
     */
    public function testCreateFromJsonSuccess(
        ResponseInterface $response,
        string $expectedClass,
        ResponseInterface $expectedResponse
    ) {
        $response = $this->responseFactory->createFromJson(json_encode($response));

        $this->assertInstanceOf($expectedClass, $response);
        $this->assertEquals($expectedResponse, $response);
    }

    public function successDataProvider(): array
    {
        $successResponse = new SuccessResponse('request_hash');
        $unknownFailureResponse = new UnknownFailureResponse('request_hash');
        $http404KnownFailureResponse = new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_HTTP, 404);
        $curl28FailureResponse = new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_CONNECTION, 28);

        return [
            'success response' => [
                'response' => new RebuildableDecoratedResponse($successResponse),
                'expectedClass' => SuccessResponse::class,
                'expectedResponse' => $successResponse,
            ],
            'unknown failure response' => [
                'response' => new RebuildableDecoratedResponse($unknownFailureResponse),
                'expectedClass' => UnknownFailureResponse::class,
                'expectedResponse' => $unknownFailureResponse,
            ],
            'http 404 failure response' => [
                'response' => new RebuildableDecoratedResponse($http404KnownFailureResponse),
                'expectedClass' => KnownFailureResponse::class,
                'expectedResponse' => $http404KnownFailureResponse,
            ],
            'curl 28 failure response' => [
                'response' => new RebuildableDecoratedResponse($curl28FailureResponse),
                'expectedClass' => KnownFailureResponse::class,
                'expectedResponse' => $curl28FailureResponse,
            ],
        ];
    }
}
