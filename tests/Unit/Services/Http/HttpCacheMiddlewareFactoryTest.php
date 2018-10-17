<?php

namespace App\Tests\Unit\Services\Http;

use App\Services\Http\HttpCache;
use App\Services\Http\HttpCacheMiddlewareFactory;
use Doctrine\Common\Cache\Cache;
use Kevinrob\GuzzleCache\CacheMiddleware;

class HttpCacheMiddlewareFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateReturnsNull()
    {
        $httpCache = \Mockery::mock(HttpCache::class);
        $httpCache
            ->shouldReceive('has')
            ->andReturn(false);

        $httpCacheMiddlewareFactory = new HttpCacheMiddlewareFactory($httpCache);

        $this->assertNull($httpCacheMiddlewareFactory->create());
    }

    public function testCreateReturnsCacheMiddleware()
    {
        $cache = \Mockery::mock(Cache::class);

        $httpCache = \Mockery::mock(HttpCache::class);
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
