<?php

namespace App\Tests\Functional\Services\Http;

use App\Services\Http\HttpCache;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\Common\Cache\MemcachedCache;

class HttpCacheTest extends AbstractFunctionalTestCase
{
    /**
     * @var HttpCache
     */
    private $httpCache;

    protected function setUp()
    {
        parent::setUp();

        $this->httpCache = self::$container->get(HttpCache::class);
    }

    public function testGet()
    {
        $this->assertInstanceOf(MemcachedCache::class, $this->httpCache->get());
    }

    public function testClear()
    {
        $id = 'foo';

        $memcachedCache = $this->httpCache->get();
        $memcachedCache->save($id, 'data');

        $this->assertTrue($memcachedCache->contains($id));

        $this->httpCache->clear();

        $this->assertFalse($memcachedCache->contains($id));
    }
}
