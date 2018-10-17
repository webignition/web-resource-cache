<?php

namespace App\Tests\Unit\Services\Http;

use App\Services\Http\HttpCache;
use App\Services\MemcachedService;
use Doctrine\Bundle\DoctrineCacheBundle\Tests\Functional\Fixtures\Memcached;
use Doctrine\Common\Cache\MemcachedCache;

class HttpCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getDataProvider
     *
     * @param MemcachedService $memcachedService
     * @param bool $expectedReturnsMemcachedCache
     */
    public function testGet(MemcachedService $memcachedService, bool $expectedReturnsMemcachedCache)
    {
        $httpCache = new HttpCache($memcachedService);
        $memcachedCache = $httpCache->get();

        if ($expectedReturnsMemcachedCache) {
            $this->assertInstanceOf(MemcachedCache::class, $memcachedCache);
        } else {
            $this->assertNull($memcachedCache);
        }
    }

    public function getDataProvider(): array
    {
        return [
            'no memcache' => [
                'memcachedService' => $this->createMemcachedService(null),
                'expectedReturnsMemcachedCache' => false,
            ],
            'has memcache' => [
                'memcachedService' => $this->createMemcachedService(\Mockery::mock(Memcached::class)),
                'expectedReturnsMemcachedCache' => true,
            ],
        ];
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param MemcachedService $memcachedService
     * @param bool $expectedHas
     */
    public function testHas(MemcachedService $memcachedService, bool $expectedHas)
    {
        $httpCache = new HttpCache($memcachedService);

        $this->assertSame($expectedHas, $httpCache->has());
    }

    public function hasDataProvider(): array
    {
        return [
            'no memcache' => [
                'memcachedService' => $this->createMemcachedService(null),
                'expectedHas' => false,
            ],
            'has memcache' => [
                'memcachedService' => $this->createMemcachedService(\Mockery::mock(Memcached::class)),
                'expectedHas' => true,
            ],
        ];
    }

    /**
     * @dataProvider clearDataProvider
     *
     * @param MemcachedService $memcachedService
     * @param bool $expectedClearReturnValue
     */
    public function testClear(MemcachedService $memcachedService, bool $expectedClearReturnValue)
    {
        $httpCache = new HttpCache($memcachedService);

        $this->assertSame($expectedClearReturnValue, $httpCache->clear());
    }

    public function clearDataProvider(): array
    {
        $memcached = \Mockery::mock(Memcached::class);
        $memcached
            ->shouldReceive('get')
            ->andReturn(null);

        $memcached
            ->shouldReceive('set')
            ->andReturn(true);

        return [
            'no memcache' => [
                'memcachedService' => $this->createMemcachedService(null),
                'expectedClearReturnValue' => false,
            ],
            'has memcache' => [
                'memcachedService' => $this->createMemcachedService($memcached),
                'expectedClearReturnValue' => true,
            ],
        ];
    }

    private function createMemcachedService($getReturnValue): MemcachedService
    {
        $memcachedService = \Mockery::mock(MemcachedService::class);
        $memcachedService
            ->shouldReceive('get')
            ->andReturn($getReturnValue);

        return $memcachedService;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
