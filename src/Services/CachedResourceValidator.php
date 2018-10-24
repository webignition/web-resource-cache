<?php

namespace App\Services;

use App\Entity\CachedResource;
use webignition\HttpCacheControlDirectives\HttpCacheControlDirectives;
use webignition\HttpCacheControlDirectives\Tokens;

class CachedResourceValidator
{
    /**
     * @var int
     */
    private $cacheControlMinFresh;

    public function __construct(int $cacheControlMinFresh)
    {
        $this->cacheControlMinFresh = $cacheControlMinFresh;
    }

    public function isFresh(CachedResource $resource): bool
    {
        if ($resource->getStoredAge() <= $this->cacheControlMinFresh) {
            return true;
        }

        $resourceHeaders = $resource->getHeaders();
        $cacheControlHeaders = $resourceHeaders->get('cache-control');
        $cacheControlDirectives = new HttpCacheControlDirectives();

        foreach ($cacheControlHeaders as $cacheControlHeader) {
            $cacheControlDirectives->addDirectives($cacheControlHeader);
        }

        if ($cacheControlDirectives->hasDirective(Tokens::NO_STORE)) {
            return false;
        }

        if ($cacheControlDirectives->hasDirective(Tokens::NO_CACHE)) {
            return false;
        }

        $hasMaxAge = $cacheControlDirectives->hasDirective(Tokens::MAX_AGE);
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
