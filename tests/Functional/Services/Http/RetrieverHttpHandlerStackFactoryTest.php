<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\RetrieverHttpHandlerStackFactory;
use App\Tests\Functional\AbstractFunctionalTestCase;
use GuzzleHttp\HandlerStack;

class RetrieverHttpHandlerStackFactoryTest extends AbstractFunctionalTestCase
{
    /**
     * @var RetrieverHttpHandlerStackFactory
     */
    private $httpHandlerStackFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->httpHandlerStackFactory = self::$container->get(RetrieverHttpHandlerStackFactory::class);
    }

    public function testCreate()
    {
        $handlerStack = $this->httpHandlerStackFactory->create();

        $this->assertInstanceOf(HandlerStack::class, $handlerStack);

        $currentHandlerStackString = (string)$handlerStack;
        $handlerStack->remove(RetrieverHttpHandlerStackFactory::MIDDLEWARE_CACHE_KEY);
        $this->assertNotSame($currentHandlerStackString, (string)$handlerStack);

        $currentHandlerStackString = (string)$handlerStack;
        $handlerStack->remove(RetrieverHttpHandlerStackFactory::MIDDLEWARE_HISTORY_KEY);
        $this->assertNotSame($currentHandlerStackString, (string)$handlerStack);
    }
}
