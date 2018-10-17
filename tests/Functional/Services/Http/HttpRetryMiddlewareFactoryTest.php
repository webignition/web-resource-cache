<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\HttpRetryMiddlewareFactory;
use App\Tests\Functional\AbstractFunctionalTestCase;

class HttpRetryMiddlewareFactoryTest extends AbstractFunctionalTestCase
{
    /**
     * @var HttpRetryMiddlewareFactory
     */
    private $httpRetryMiddlewareFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->httpRetryMiddlewareFactory = self::$container->get(HttpRetryMiddlewareFactory::class);
    }

    public function testCreate()
    {
        $this->assertInstanceOf(\Closure::class, $this->httpRetryMiddlewareFactory->create());
    }
}
