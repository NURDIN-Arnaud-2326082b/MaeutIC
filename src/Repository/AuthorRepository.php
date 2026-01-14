<?php

namespace App\Repository;

use App\Entity\Author;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuthorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Author::class);
    }

    public function findById(int $id): ?Author
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function addAuthor(Author $author): void
    {
        $this->getEntityManager()->persist($author);
        $this->getEntityManager()->flush();
    }

    public function removeAuthor(Author $author): void
    {
        $this->getEntityManager()->remove($author);
        $this->getEntityManager()->flush();
    }

    public function editAuthor(Author $author): void
    {
        $this->getEntityManager()->persist($author);
        $this->getEntityManager()->flush();
    }

    public function findByTags(array $tagIds, $taggableRepository): array
    {
        if (empty($tagIds)) {
            return $this->findAllOrderedByName();
        }
        // On récupère les entityId (author.id) ayant tous les tags demandés
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('App\Entity\Taggable', 't', 'WITH', 't.entityId = a.id AND t.entityType = :type')
            ->where('t.tag IN (:tagIds)')
            ->setParameter('type', 'author')
            ->setParameter('tagIds', $tagIds)
            ->groupBy('a.id')
            ->having('COUNT(DISTINCT t.tag) = :count')
            ->setParameter('count', count($tagIds))
            ->orderBy('a.name', 'ASC');
        return $qb->getQuery()->getResult();
    }

    /**
     * Find all authors ordered by name.
     *
     * @return Author[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}