<?php

namespace App\Repository;

use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function add(Report $report): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($report);
        $entityManager->flush();
    }

    public function remove(Report $report): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($report);
        $entityManager->flush();
    }

    public function findOneById(int $id): ?Report
    {
        return $this->getEntityManager()
            ->getRepository(Report::class)
            ->find($id);
    }

    public function findAll(): array
    {
        return $this->getEntityManager()
          ->getRepository(Report::class)
          ->findBy([]);
    }
}
