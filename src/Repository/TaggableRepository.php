<?php

namespace App\Repository;

use App\Entity\Taggable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaggableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Taggable::class);
    }

    /**
     * @return Taggable[] Returns an array of Taggable objects
     */
    public function findByTypeAndId($type, $id): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.entityType = :type')
            ->andWhere('t.entityId = :id')
            ->setParameter('type', $type)
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }
}