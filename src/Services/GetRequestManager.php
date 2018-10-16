<?php

namespace App\Services;

use App\Entity\GetRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class GetRequestManager
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
        $this->repository = $entityManager->getRepository(GetRequest::class);
    }

    public function find(string $url): ?GetRequest
    {
        /* @var GetRequest $getRequest */
        $getRequest = $this->repository->findOneBy([
            'url' => $url,
        ]);

        return $getRequest;
    }

    public function persist(GetRequest $getRequest)
    {
        $this->entityManager->persist($getRequest);
        $this->entityManager->flush();
    }
}
