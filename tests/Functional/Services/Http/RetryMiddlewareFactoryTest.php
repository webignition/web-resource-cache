<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\RetryMiddlewareFactory;
use App\Tests\Functional\AbstractFunctionalTestCase;

class RetryMiddlewareFactoryTest extends AbstractFunctionalTestCase
{
    /**
     * @var RetryMiddlewareFactory
     */
    private $httpRetryMiddlewareFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->httpRetryMiddlewareFactory = self::$container->get(RetryMiddlewareFactory::class);
    }

    public function testCreate()
    {
        $this->assertInstanceOf(\Closure::class, $this->httpRetryMiddlewareFactory->create());
    }
}
