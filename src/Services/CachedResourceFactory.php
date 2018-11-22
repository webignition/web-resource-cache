<?php

namespace App\Services;

use App\Entity\CachedResource;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use webignition\HttpHeaders\Headers;

class CachedResourceFactory
{
    public function create(string $requestHash, string $url, HttpResponseInterface $response): ?CachedResource
    {
        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $cachedResource = new CachedResource();

        $this->updateResponse($cachedResource, $response);

        $cachedResource->setRequestHash($requestHash);
        $cachedResource->setUrl($url);

        return $cachedResource;
    }

    public function updateResponse(CachedResource $cachedResource, HttpResponseInterface $response)
    {
        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $responseContent = $response->getBody()->getContents();

        $cachedResource->setHeaders(new Headers($response->getHeaders()));
        $cachedResource->setBody($responseContent);
    }
}
