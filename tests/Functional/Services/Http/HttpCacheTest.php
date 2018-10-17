<?php

namespace App\Tests\Functional\Services\Http;

use App\Entity\RetrieveRequest;
use App\Exception\TransportException;
use App\Services\Http\HttpCache;
use App\Services\ResourceRetriever;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Tests\Services\HttpMockHandler;
use App\Tests\UnhandledGuzzleException;
use Doctrine\Common\Cache\MemcachedCache;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

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

    public function testHas()
    {
        $this->assertTrue($this->httpCache->has());
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
