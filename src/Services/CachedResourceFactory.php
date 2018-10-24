<?php

namespace App\Services;

use App\Entity\CachedResource;
use App\Entity\RetrieveRequest;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use webignition\HttpHeaders\Headers;

class CachedResourceFactory
{
    public function create(RetrieveRequest $retrieveRequest, HttpResponseInterface $response): ?CachedResource
    {
        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $cachedResource = new CachedResource();

        $cachedResource->setHeaders(new Headers($response->getHeaders()));
        $cachedResource->setBody((string) $response->getBody());

        $cachedResource->setRequestHash($retrieveRequest->getHash());
        $cachedResource->setUrl($retrieveRequest->getUrl());

        return $cachedResource;
    }
}
