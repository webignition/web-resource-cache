<?php

namespace App\Services;

use App\Entity\Callback as CallbackEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class CallbackManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(CallbackEntity::class);
    }

    /**
     * @param string $requestHash
     *
     * @return CallbackEntity[]
     */
    public function findByRequestHash(string $requestHash): array
    {
        return $this->repository->findBy([
            'requestHash' => $requestHash,
        ]);
    }

    public function findByRequestHashAndUrl(string $requestHash, string $url): ?CallbackEntity
    {
        $callback = $this->repository->findOneBy([
            'requestHash' => $requestHash,
            'url' => $url,
        ]);

        return $callback;
    }

    public function persist(CallbackEntity $callback)
    {
        $this->entityManager->persist($callback);
        $this->entityManager->flush();
    }
}
