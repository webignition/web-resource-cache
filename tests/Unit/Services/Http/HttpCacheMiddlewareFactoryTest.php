<?php

namespace App\Tests\Unit\Services\Http;

use App\Services\Http\Cache;
use App\Services\Http\HttpCacheMiddlewareFactory;
use Doctrine\Common\Cache\MemcachedCache;
use Kevinrob\GuzzleCache\CacheMiddleware;

class HttpCacheMiddlewareFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateReturnsCacheMiddleware()
    {
        $cache = \Mockery::mock(MemcachedCache::class);

        $httpCache = \Mockery::mock(Cache::class);
        $httpCache
            ->shouldReceive('has')
            ->andReturn(true);

        $httpCache
            ->shouldReceive('get')
            ->andReturn($cache);

        $httpCacheMiddlewareFactory = new HttpCacheMiddlewareFactory($httpCache);

        $this->assertInstanceOf(CacheMiddleware::class, $httpCacheMiddlewareFactory->create());
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
