<?php

namespace App\Tests\Unit\Services;

use App\Model\Response\AbstractFailureResponse;
use App\Model\Response\AbstractResponse;
use App\Model\Response\KnownFailureResponse;
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
            'missing status' => [
                'data' => [
                    'request_id' => 'request_hash',
                ],
            ],
            'invalid status' => [
                'data' => [
                    'request_id' => 'request_hash',
                    'status' => 'foo',
                ],
            ],
            'missing failure_type' => [
                'data' => [
                    'request_id' => 'request_hash',
                    'status' => AbstractResponse::STATUS_FAILED,
                ],
            ],
            'invalid failure_type' => [
                'data' => [
                    'request_id' => 'request_hash',
                    'status' => AbstractResponse::STATUS_FAILED,
                    'failure_type' => 'foo',
                ],
            ],
            'missing status_code' => [
                'data' => [
                    'request_id' => 'request_hash',
                    'status' => AbstractResponse::STATUS_FAILED,
                    'failure_type' => AbstractFailureResponse::TYPE_HTTP,
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

    public function successDataProvider(): array
    {
        $successResponse = new SuccessResponse('request_hash');
        $unknownFailureResponse = new UnknownFailureResponse('request_hash');
        $http404KnownFailureResponse = new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_HTTP, 404);
        $curl28FailureResponse = new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_CONNECTION, 28);

        return [
            'success response' => [
                'response' => $successResponse,
                'expectedClass' => SuccessResponse::class,
                'expectedResponse' => $successResponse,
            ],
            'unknown failure response' => [
                'response' => $unknownFailureResponse,
                'expectedClass' => UnknownFailureResponse::class,
                'expectedResponse' => $unknownFailureResponse,
            ],
            'http 404 failure response' => [
                'response' => $http404KnownFailureResponse,
                'expectedClass' => KnownFailureResponse::class,
                'expectedResponse' => $http404KnownFailureResponse,
            ],
            'curl 28 failure response' => [
                'response' => $curl28FailureResponse,
                'expectedClass' => KnownFailureResponse::class,
                'expectedResponse' => $curl28FailureResponse,
            ],
        ];
    }
}
