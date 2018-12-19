<?php
/** @noinspection PhpDocSignatureInspection */

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
     */
    public function testCreateFromArraySuccess(
        ResponseInterface $response,
        string $expectedClass,
        ResponseInterface $expectedResponse,
        array $expectedResponseData
    ) {
        $response = $this->responseFactory->createFromArray($response->jsonSerialize());

        $this->assertInstanceOf($expectedClass, $response);
        $this->assertEquals($expectedResponse, $response);
        $this->assertEquals($expectedResponseData, $response->jsonSerialize());
    }

    public function successDataProvider(): array
    {
        $successResponse = new SuccessResponse('request_hash');
        $unknownFailureResponse = new UnknownFailureResponse('request_hash');
        $http404KnownFailureResponse = new KnownFailureResponse(
            'request_hash',
            KnownFailureResponse::TYPE_HTTP,
            301,
            [
                'too_many_redirects' => true,
                'is_redirect_loop' => false,
                'history' => [
                    'http://example.com/',
                    'http://example.com/1',
                    'http://example.com/2',
                ],
            ]
        );
        $curl28FailureResponse = new KnownFailureResponse('request_hash', KnownFailureResponse::TYPE_CONNECTION, 28);

        return [
            'success response' => [
                'response' => $successResponse,
                'expectedClass' => SuccessResponse::class,
                'expectedResponse' => $successResponse,
                'expectedResponseData' => [
                    'request_id' => 'request_hash',
                    'status' => 'success',
                ],
            ],
            'unknown failure response' => [
                'response' => $unknownFailureResponse,
                'expectedClass' => UnknownFailureResponse::class,
                'expectedResponse' => $unknownFailureResponse,
                'expectedResponseData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => 'unknown',
                ],
            ],
            'http 301 failure response' => [
                'response' => $http404KnownFailureResponse,
                'expectedClass' => KnownFailureResponse::class,
                'expectedResponse' => $http404KnownFailureResponse,
                'expectedResponseData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => 'http',
                    'status_code' => 301,
                    'context' => [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => false,
                        'history' => [
                            'http://example.com/',
                            'http://example.com/1',
                            'http://example.com/2',
                        ],
                    ],
                ],
            ],
            'curl 28 failure response' => [
                'response' => $curl28FailureResponse,
                'expectedClass' => KnownFailureResponse::class,
                'expectedResponse' => $curl28FailureResponse,
                'expectedResponseData' => [
                    'request_id' => 'request_hash',
                    'status' => 'failed',
                    'failure_type' => 'connection',
                    'status_code' => 28,
                    'context' => [],
                ],
            ],
        ];
    }
}
