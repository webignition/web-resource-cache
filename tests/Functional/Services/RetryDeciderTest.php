<?php

namespace App\Tests\Functional\Services;

use App\Services\RetryDecider;
use App\Tests\Functional\AbstractFunctionalTestCase;

class RetryDeciderTest extends AbstractFunctionalTestCase
{
    /**
     * @var RetryDecider
     */
    private $retryDecider;

    protected function setUp()
    {
        parent::setUp();

        $this->retryDecider = self::$container->get(RetryDecider::class);
    }

    /**
     * @dataProvider isRetryableDataProvider
     *
     * @param string $type
     * @param int $code
     * @param bool $expectedIsRetryable
     */
    public function testIsRetryable(string $type, int $code, bool $expectedIsRetryable)
    {
        $this->assertEquals($expectedIsRetryable, $this->retryDecider->isRetryable($type, $code));
    }

    public function isRetryableDataProvider(): array
    {
        return [
            'invalid type' => [
                'type' => 'foo',
                'code' => 0,
                'expectedIsRetryable' => false,
            ],
            'http 400 not retryable' => [
                'type' => RetryDecider::TYPE_HTTP,
                'code' => 400,
                'expectedIsRetryable' => false,
            ],
            'http 404 not retryable' => [
                'type' => RetryDecider::TYPE_HTTP,
                'code' => 404,
                'expectedIsRetryable' => false,
            ],
            'http 500 not retryable' => [
                'type' => RetryDecider::TYPE_HTTP,
                'code' => 500,
                'expectedIsRetryable' => false,
            ],
            'http 408 is retryable' => [
                'type' => RetryDecider::TYPE_HTTP,
                'code' => 408,
                'expectedIsRetryable' => true,
            ],
            'http 429 is retryable' => [
                'type' => RetryDecider::TYPE_HTTP,
                'code' => 429,
                'expectedIsRetryable' => true,
            ],
            'http 503 is retryable' => [
                'type' => RetryDecider::TYPE_HTTP,
                'code' => 503,
                'expectedIsRetryable' => true,
            ],
            'http 504 is retryable' => [
                'type' => RetryDecider::TYPE_HTTP,
                'code' => 504,
                'expectedIsRetryable' => true,
            ],
            'curl 54 not retryable' => [
                'type' => RetryDecider::TYPE_CURL,
                'code' => 54,
                'expectedIsRetryable' => false,
            ],
            'curl 6 is retryable' => [
                'type' => RetryDecider::TYPE_CURL,
                'code' => 6,
                'expectedIsRetryable' => true,
            ],
            'curl 28 is retryable' => [
                'type' => RetryDecider::TYPE_CURL,
                'code' => 28,
                'expectedIsRetryable' => true,
            ],
        ];
    }
}
