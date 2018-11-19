<?php

namespace App\Tests\Unit\Services;

use App\Entity\Callback;
use App\Services\CallbackFactory;

class CallbackFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CallbackFactory
     */
    private $callbackFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->callbackFactory = new CallbackFactory();
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $requestHash
     * @param string $url
     * @param bool $logResponse
     */
    public function testCreate(string $requestHash, string $url, bool $logResponse)
    {
        $callback = $this->callbackFactory->create($requestHash, $url, $logResponse);

        $this->assertInstanceOf(Callback::class, $callback);
        $this->assertNull($callback->getId());

        $this->assertEquals($requestHash, $callback->getRequestHash());
        $this->assertEquals($url, $callback->getUrl());
        $this->assertEquals(0, $callback->getRetryCount());
        $this->assertFalse($logResponse, $callback->getLogResponse());
    }

    public function createDataProvider(): array
    {
        return [
            'logResponse: false' => [
                'requestHash' => 'request_hash_1',
                'url' => 'http://example.com/1/',
                'logResponse' => false,
            ],
            'logResponse: true' => [
                'requestHash' => 'request_hash_1',
                'url' => 'http://example.com/1/',
                'logResponse' => false,
            ],
        ];
    }
}
