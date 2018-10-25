<?php

namespace App\Services\Http;

use Doctrine\Common\Cache\Cache as DoctrineCache;
use Doctrine\Common\Cache\MemcachedCache;

class Cache
{
    /**
     * @var MemcachedCache
     */
    private $memcachedCache;

    public function __construct(MemcachedCache $memcachedCache)
    {
        $this->memcachedCache = $memcachedCache;
    }

    /**
     * @return MemcachedCache|DoctrineCache
     */
    public function get()
    {
        return $this->memcachedCache;
    }

    public function clear(): bool
    {
        return $this->get()->deleteAll();
    }
}
