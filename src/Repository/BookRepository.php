<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * Find all books ordered by name.
     *
     * @return Book[]
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
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('App\\Entity\\Taggable', 't', 'WITH', 't.entityId = b.id AND t.entityType = :type')
            ->where('t.tag IN (:tagIds)')
            ->setParameter('type', 'book')
            ->setParameter('tagIds', $tagIds)
            ->groupBy('b.id')
            ->having('COUNT(DISTINCT t.tag) = :count')
            ->setParameter('count', count($tagIds))
            ->orderBy('b.title', 'ASC');
        return $qb->getQuery()->getResult();
    }
}