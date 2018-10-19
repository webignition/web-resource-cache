<?php

namespace App\Services;

use App\Entity\Resource;
use webignition\HttpCacheControlDirectives\HttpCacheControlDirectives;

class ResourceValidator
{
    /**
     * @var int
     */
    private $cacheControlMinFresh;

    public function __construct(int $cacheControlMinFresh)
    {
        $this->cacheControlMinFresh = $cacheControlMinFresh;
    }

    public function isFresh(Resource $resource): bool
    {
        if ($resource->getStoredAge() <= $this->cacheControlMinFresh) {
            return true;
        }

        $resourceHeaders = $resource->getHeaders();
        $cacheControlDirectives = new HttpCacheControlDirectives($resourceHeaders->get('cache-control') ?? '');

        if ($cacheControlDirectives->hasDirective(HttpCacheControlDirectives::NO_STORE)) {
            return false;
        }

        if ($cacheControlDirectives->hasDirective(HttpCacheControlDirectives::NO_CACHE)) {
            return false;
        }

        $hasMaxAge = $cacheControlDirectives->hasDirective(HttpCacheControlDirectives::MAX_AGE);
        if ($hasMaxAge && $resource->getStoredAge() <= $cacheControlDirectives->getMaxAge()) {
            return true;
        }

        if ($resourceHeaders->hasExpired()) {
            return false;
        }

        $age = $resourceHeaders->getAge();
        if (null !== $age && $this->cacheControlMinFresh > $age) {
            return true;
        }

        return false;
    }
}
