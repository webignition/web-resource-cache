<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\SenderHttpHandlerStackFactory;
use App\Tests\Functional\AbstractFunctionalTestCase;
use GuzzleHttp\HandlerStack;

class SenderHttpHandlerStackFactoryTest extends AbstractFunctionalTestCase
{
    /**
     * @var SenderHttpHandlerStackFactory
     */
    private $httpHandlerStackFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->httpHandlerStackFactory = self::$container->get(SenderHttpHandlerStackFactory::class);
    }

    public function testCreate()
    {
        $handlerStack = $this->httpHandlerStackFactory->create();

        $this->assertInstanceOf(HandlerStack::class, $handlerStack);
    }
}
