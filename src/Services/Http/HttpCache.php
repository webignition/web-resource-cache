<?php

namespace App\Services\Http;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\MemcachedCache;

class HttpCache
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
     * @return MemcachedCache|Cache
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
