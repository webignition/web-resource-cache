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

    public function testCreate()
    {
        $requestHash = 'request_hash';
        $url = 'http://example.com/';

        $callback = $this->callbackFactory->create($requestHash, $url);

        $this->assertInstanceOf(Callback::class, $callback);
        $this->assertNull($callback->getId());
        $this->assertEquals($requestHash, $callback->getRequestHash());
        $this->assertEquals($url, $callback->getUrl());
        $this->assertEquals(0, $callback->getRetryCount());
    }
}
