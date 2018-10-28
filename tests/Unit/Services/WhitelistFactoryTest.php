<?php

namespace App\Tests\Unit\Services;

use App\Services\Whitelist;
use App\Services\WhitelistFactory;

class WhitelistFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testCreateWithEmptyPatternsString()
    {
        $whitelistFactory = new WhitelistFactory();
        $whitelist = $whitelistFactory->create('');

        $this->assertTrue(true);

        $reflector = new \ReflectionClass(Whitelist::class);
        $property = $reflector->getProperty('whitelistItems');
        $property->setAccessible(true);


        $whitelistItems = $property->getValue($whitelist);

        $this->assertEmpty($whitelistItems);
    }
}
