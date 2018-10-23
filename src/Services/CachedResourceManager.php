<?php

namespace App\Services;

use App\Entity\CachedResource;
use App\Model\Headers;
use App\Model\RequestIdentifier;
use Doctrine\ORM\EntityManagerInterface;

class CachedResourceManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(
        RequestIdentifier $requestIdentifier,
        string $url,
        Headers $headers,
        string $body
    ): CachedResource {
        $cachedResource = new CachedResource();
        $cachedResource->setRequestHash($requestIdentifier);
        $cachedResource->setUrl($url);
        $cachedResource->setHeaders($headers);
        $cachedResource->setBody($body);
        $cachedResource->setLastStored(new \DateTime());

        $this->entityManager->persist($cachedResource);
        $this->entityManager->flush();

        return $cachedResource;
    }

    public function update(CachedResource $cachedResource)
    {
        $cachedResource->setLastStored(new \DateTime());

        $this->entityManager->persist($cachedResource);
        $this->entityManager->flush();
    }

    public function find(string $requestHash): ?CachedResource
    {
        return $this->entityManager->find(CachedResource::class, $requestHash);
    }
}
