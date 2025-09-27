<?php

namespace App\Repository;

use App\Entity\Reading;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReadingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reading::class);
    }

    public function add(Reading $reading): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($reading);
        $entityManager->flush();
    }

    public function remove(Reading $reading): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($reading);
        $entityManager->flush();
    }

    public function findOneById(int $id): ?Reading
    {
        return $this->getEntityManager()
            ->getRepository(Reading::class)
            ->find($id);
    }

    public function findAll(): array
    {
        return $this->getEntityManager()
          ->getRepository(Reading::class)
          ->findBy([]);
    }
}
