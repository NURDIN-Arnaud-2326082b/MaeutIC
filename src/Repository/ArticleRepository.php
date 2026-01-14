<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Find all articles ordered by name.
     *
     * @return Article[]
     */
    public function findAllOrderedByTitle(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTags(array $tagIds, $taggableRepository): array
    {
        if (empty($tagIds)) {
            return $this->findAllOrderedByTitle();
        }
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('App\\Entity\\Taggable', 't', 'WITH', 't.entityId = a.id AND t.entityType = :type')
            ->where('t.tag IN (:tagIds)')
            ->setParameter('type', 'article')
            ->setParameter('tagIds', $tagIds)
            ->groupBy('a.id')
            ->having('COUNT(DISTINCT t.tag) = :count')
            ->setParameter('count', count($tagIds))
            ->orderBy('a.title', 'ASC');
        return $qb->getQuery()->getResult();
    }
}