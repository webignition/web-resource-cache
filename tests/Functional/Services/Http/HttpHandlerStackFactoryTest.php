<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\HttpHandlerStackFactory;
use App\Tests\Functional\AbstractFunctionalTestCase;
use GuzzleHttp\HandlerStack;

class HttpHandlerStackFactoryTest extends AbstractFunctionalTestCase
{
    /**
     * @var HttpHandlerStackFactory
     */
    private $httpHandlerStackFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->httpHandlerStackFactory = self::$container->get(HttpHandlerStackFactory::class);
    }

    public function testCreate()
    {
        $handlerStack = $this->httpHandlerStackFactory->create();

        $this->assertInstanceOf(HandlerStack::class, $handlerStack);

        $currentHandlerStackString = (string)$handlerStack;
        $handlerStack->remove(HttpHandlerStackFactory::MIDDLEWARE_CACHE_KEY);
        $this->assertNotSame($currentHandlerStackString, (string)$handlerStack);

        $currentHandlerStackString = (string)$handlerStack;
        $handlerStack->remove(HttpHandlerStackFactory::MIDDLEWARE_HISTORY_KEY);
        $this->assertNotSame($currentHandlerStackString, (string)$handlerStack);
    }
}
