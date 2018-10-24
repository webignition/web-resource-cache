<?php

namespace App\Services;

use App\Entity\CachedResource;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use webignition\HttpHeaders\Headers;

class CachedResourceFactory
{
    public function createFromPsr7Response(HttpResponseInterface $response): ?CachedResource
    {
        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $cachedResource = new CachedResource();

        $cachedResource->setHeaders(new Headers($response->getHeaders()));
        $cachedResource->setBody((string) $response->getBody());

        return $cachedResource;
    }
}
