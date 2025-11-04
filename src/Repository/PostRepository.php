<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findByForum($forumName): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.forum', 'f')
            ->where('f.title = :forumName')
            ->setParameter('forumName', $forumName)
            ->orderBy('p.creationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function addPost(Post $post): void
    {
        $this->getEntityManager()->persist($post);
        $this->getEntityManager()->flush();
    }

    public function removePost(Post $post): void
    {
        $this->getEntityManager()->remove($post);
        $this->getEntityManager()->flush();
    }

    public function findRepliesByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.isReply = :isReply')
            ->setParameter('user', $user)
            ->setParameter('isReply', true)
            ->orderBy('p.creationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //     * @return Post[] Returns an array of Post objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Post
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function searchPosts(string $query, string $type = 'all', string $dateFilter = 'all', string $sortBy = 'recent', string $category = 'General'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.forum', 'f')
            ->leftJoin('p.user', 'u')
            //->andWhere('p.isReply = false') // Si on veut exclure les réponses
            ->orderBy('p.creationDate', 'DESC');

        // Filtre par catégorie
        if ($category !== 'General') {
            $qb->andWhere('f.title = :category')
            ->setParameter('category', $category);
        }

        // Filtre par texte de recherche
        if (!empty($query)) {
            switch ($type) {
                case 'title':
                    $qb->andWhere('p.name LIKE :query')
                    ->setParameter('query', '%' . $query . '%');
                    break;
                case 'content':
                    $qb->andWhere('p.description LIKE :query')
                    ->setParameter('query', '%' . $query . '%');
                    break;
                case 'author':
                    // Pour la recherche par auteur, on exclut les forums anonymes
                    $qb->andWhere('(u.firstName LIKE :query OR u.lastName LIKE :query)')
                    ->andWhere('f.anonymous = false')
                    ->setParameter('query', '%' . $query . '%');
                    break;
                case 'all':
                default:
                    // Pour la recherche "Tout", on cherche dans titre + contenu + auteur (seulement si forum est non anonyme)
                    $qb->andWhere('(p.name LIKE :query OR p.description LIKE :query OR (f.anonymous = false AND (u.firstName LIKE :query OR u.lastName LIKE :query)))')
                    ->setParameter('query', '%' . $query . '%');
                    break;
            }
        }

        // Filtre par date
        $now = new \DateTime();
        switch ($dateFilter) {
            case 'today':
                $qb->andWhere('p.creationDate >= :today')
                ->setParameter('today', $now->format('Y-m-d 00:00:00'));
                break;
            case 'week':
                $weekAgo = clone $now;
                $weekAgo->modify('-1 week');
                $qb->andWhere('p.creationDate >= :weekAgo')
                ->setParameter('weekAgo', $weekAgo);
                break;
            case 'month':
                $monthAgo = clone $now;
                $monthAgo->modify('-1 month');
                $qb->andWhere('p.creationDate >= :monthAgo')
                ->setParameter('monthAgo', $monthAgo);
                break;
            case 'year':
                $yearAgo = clone $now;
                $yearAgo->modify('-1 year');
                $qb->andWhere('p.creationDate >= :yearAgo')
                ->setParameter('yearAgo', $yearAgo);
                break;
        }

        // Tri
        switch ($sortBy) {
            case 'popular':
                // Tri par nombre de likes (le plus populaire en premier)
                $qb->leftJoin('p.likes', 'pl')
                ->addSelect('COUNT(pl.id) as HIDDEN likeCount')
                ->groupBy('p.id')
                ->orderBy('likeCount', 'DESC');
                break;
                
            case 'commented':
                // Tri par nombre de commentaires (le plus commenté en premier)
                $qb->leftJoin('p.comments', 'c')
                ->addSelect('COUNT(c.id) as HIDDEN commentCount')
                ->groupBy('p.id')
                ->orderBy('commentCount', 'DESC');
                break;
                
            case 'recent':
                $qb->orderBy('p.creationDate', 'DESC');
                break;
            default:
                // Tri par date de création (le plus récent en premier)
                $qb->orderBy('p.creationDate', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function findByMethodologyForums()
    {
        return $this->createQueryBuilder('p')
            ->join('p.forum', 'f')
            ->where('f.special = :special')
            ->setParameter('special', 'methodology')
            ->orderBy('p.creationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

}
