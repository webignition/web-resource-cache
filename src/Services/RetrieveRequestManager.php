<?php

namespace App\Services;

use App\Entity\RetrieveRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class RetrieveRequestManager
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
        $this->repository = $entityManager->getRepository(RetrieveRequest::class);
    }

    public function find(string $hash): ?RetrieveRequest
    {
        /* @var RetrieveRequest $retrieveRequest */
        $retrieveRequest = $this->repository->find($hash);

        return $retrieveRequest;
    }

    public function persist(RetrieveRequest $retrieveRequest)
    {
        $this->entityManager->persist($retrieveRequest);
        $this->entityManager->flush();
    }
}
