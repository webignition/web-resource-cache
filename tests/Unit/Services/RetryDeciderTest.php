<?php

namespace App\Tests\Unit\Services;

use App\Services\RetryDecider;

class RetryDeciderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isRetryableDataProvider
     *
     * @param array $retryableStatusesData
     * @param string $type
     * @param int $code
     * @param bool $expectedIsRetryable
     */
    public function testIsRetryable(array $retryableStatusesData, string $type, int $code, bool $expectedIsRetryable)
    {
        $retryDecider = new RetryDecider($retryableStatusesData);

        $this->assertEquals($expectedIsRetryable, $retryDecider->isRetryable($type, $code));
    }

    public function isRetryableDataProvider(): array
    {
        return [
            'no data' => [
                'retryableStatusesData' => [],
                'type' => RetryDecider::TYPE_HTTP,
                'code' => 0,
                'expectedIsRetryable' => false,
            ],
            'not retryable; type not present' => [
                'retryableStatusesData' => [
                    RetryDecider::TYPE_HTTP => [],
                ],
                'type' => RetryDecider::TYPE_CONNECTION,
                'code' => 0,
                'expectedIsRetryable' => false,
            ],
            'not retryable; code not present' => [
                'retryableStatusesData' => [
                    RetryDecider::TYPE_HTTP => [
                        404,
                    ],
                ],
                'type' => RetryDecider::TYPE_HTTP,
                'code' => 0,
                'expectedIsRetryable' => false,
            ],
            'is retryable' => [
                'retryableStatusesData' => [
                    RetryDecider::TYPE_HTTP => [
                        404,
                    ],
                ],
                'type' => RetryDecider::TYPE_HTTP,
                'code' => 404,
                'expectedIsRetryable' => true,
            ],
        ];
    }
}
