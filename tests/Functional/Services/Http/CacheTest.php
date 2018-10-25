<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\Cache;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\Common\Cache\MemcachedCache;

class CacheTest extends AbstractFunctionalTestCase
{
    /**
     * @var Cache
     */
    private $cache;

    protected function setUp()
    {
        parent::setUp();

        $this->cache = self::$container->get(Cache::class);
    }

    public function testGet()
    {
        $this->assertInstanceOf(MemcachedCache::class, $this->cache->get());
    }

    public function testClear()
    {
        $id = 'foo';

        $memcachedCache = $this->cache->get();
        $memcachedCache->save($id, 'data');

        $this->assertTrue($memcachedCache->contains($id));

        $this->cache->clear();

        $this->assertFalse($memcachedCache->contains($id));
    }
}
