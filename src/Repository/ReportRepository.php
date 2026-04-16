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

    public function deletePendingByTargetExceptId(string $targetType, int $targetId, int $excludedReportId): int
    {
        return $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.targetType = :targetType')
            ->andWhere('r.targetId = :targetId')
            ->andWhere('r.status = :status')
            ->andWhere('r.id != :excludedReportId')
            ->setParameter('targetType', $targetType)
            ->setParameter('targetId', $targetId)
            ->setParameter('status', Report::STATUS_PENDING)
            ->setParameter('excludedReportId', $excludedReportId)
            ->getQuery()
            ->execute();
    }
}
