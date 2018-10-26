<?php

namespace App\Services;

use App\Entity\CachedResource;
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
