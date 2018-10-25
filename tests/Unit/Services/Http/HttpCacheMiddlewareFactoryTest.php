<?php

namespace App\Tests\Unit\Services\Http;

use App\Services\Http\Cache;
use App\Services\Http\CacheMiddlewareFactory;
use Doctrine\Common\Cache\MemcachedCache;
use Kevinrob\GuzzleCache\CacheMiddleware;

class HttpCacheMiddlewareFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateReturnsCacheMiddleware()
    {
        $memcachedCache = \Mockery::mock(MemcachedCache::class);

        $cache = \Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('has')
            ->andReturn(true);

        $cache
            ->shouldReceive('get')
            ->andReturn($memcachedCache);

        $cacheMiddlewareFactory = new CacheMiddlewareFactory($cache);

        $this->assertInstanceOf(CacheMiddleware::class, $cacheMiddlewareFactory->create());
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
