<?php

namespace App\Tests\Unit\Services;

use App\Model\WhitelistItem;
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

        $reflector = new \ReflectionClass(Whitelist::class);
        $property = $reflector->getProperty('whitelistItems');
        $property->setAccessible(true);

        $whitelistItems = $property->getValue($whitelist);

        $this->assertEmpty($whitelistItems);
    }

    /**
     * @throws \ReflectionException
     */
    public function testPatternsAreWrappedInRegexDelimiters()
    {
        $whitelistFactory = new WhitelistFactory();
        $whitelist = $whitelistFactory->create('foo,bar');

        $this->assertTrue(true);

        $reflector = new \ReflectionClass(Whitelist::class);
        $property = $reflector->getProperty('whitelistItems');
        $property->setAccessible(true);

        $whitelistItems = $property->getValue($whitelist);

        $this->assertEquals(
            [
                new WhitelistItem('/foo/'),
                new WhitelistItem('/bar/'),
            ],
            $whitelistItems
        );

        $this->assertTrue($whitelist->matches('foo'));
        $this->assertTrue($whitelist->matches('bar'));
    }
}
