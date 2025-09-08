<?php

namespace App\Repository;

use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Resource>
 */
class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    public function findByPage(string $page, int $offset = 0): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.page = :page')
            ->setParameter('page', $page)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
