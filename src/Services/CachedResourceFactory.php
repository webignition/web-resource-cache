<?php

namespace App\Services;

use App\Entity\CachedResource;
use App\Model\RetrieveRequest;
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

        $this->updateResponse($cachedResource, $response);

        $cachedResource->setRequestHash($retrieveRequest->getRequestHash());
        $cachedResource->setUrl($retrieveRequest->getUrl());

        return $cachedResource;
    }

    public function updateResponse(CachedResource $cachedResource, HttpResponseInterface $response)
    {
        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $cachedResource->setHeaders(new Headers($response->getHeaders()));
        $cachedResource->setBody((string) $response->getBody());
    }
}
