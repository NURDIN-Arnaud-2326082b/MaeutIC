<?php

namespace App\Repository;

use App\Entity\UserLike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLike::class);
    }

    public function add(UserLike $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserLike $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function countByCommentId(int $commentId): int
    {
        return $this->createQueryBuilder('ul')
            ->select('COUNT(ul.id)')
            ->where('ul.comment = :commentId')
            ->setParameter('commentId', $commentId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasUserLikedComment(int $userId, int $commentId): bool
    {
        return (bool) $this->createQueryBuilder('ul')
            ->select('COUNT(ul.id)')
            ->where('ul.user = :userId')
            ->andWhere('ul.comment = :commentId')
            ->setParameter('userId', $userId)
            ->setParameter('commentId', $commentId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}