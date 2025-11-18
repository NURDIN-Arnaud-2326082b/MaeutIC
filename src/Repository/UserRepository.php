<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByTaggableQuestion1Tags(array $tagIds, int $limit = 1000): array
    {
        if (empty($tagIds)) {
            return $this->createQueryBuilder('u')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        }

        $tagNames = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t.name')
            ->from('App\Entity\Tag', 't')
            ->where('t.id IN (:tagIds)')
            ->setParameter('tagIds', $tagIds)
            ->getQuery()
            ->getResult();

        $tagNames = array_column($tagNames, 'name');

        if (empty($tagNames)) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->join('u.userQuestions', 'uq')
            ->where('uq.question = :question')
            ->andWhere('uq.answer IN (:tagNames)')
            ->setParameter('question', 'Taggable Question 0')
            ->setParameter('tagNames', $tagNames)
            ->groupBy('u.id')
            ->having('COUNT(DISTINCT uq.answer) = :nbTags')
            ->setParameter('nbTags', count($tagNames))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findBySearchQuery(string $query, int $limit = 1000): array
    {
        if (empty(trim($query))) {
            return $this->createQueryBuilder('u')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        }

        $searchTerms = $this->extractSearchTerms($query);

        if (empty($searchTerms)) {
            return [];
        }

        $qb = $this->createQueryBuilder('u');

        foreach ($searchTerms as $key => $term) {
            $qb
                ->orWhere('u.username LIKE :term_' . $key)
                ->orWhere('u.firstName LIKE :term_' . $key)
                ->orWhere('u.lastName LIKE :term_' . $key)
                ->orWhere('u.affiliationLocation LIKE :term_' . $key)
                ->orWhere('u.specialization LIKE :term_' . $key)
                ->orWhere('u.researchTopic LIKE :term_' . $key)
                ->setParameter('term_' . $key, '%' . $term . '%');
        }

        return $qb
            ->orderBy('u.username', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les utilisateurs non-amis pour les recommandations
     */
    public function findNonFriendUsers(User $currentUser, array $friendIds, int $limit = 500): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.id != :currentUserId')
            ->setParameter('currentUserId', $currentUser->getId());

        if (!empty($friendIds)) {
            $qb->andWhere('u.id NOT IN (:friendIds)')
               ->setParameter('friendIds', $friendIds);
        }

        return $qb
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total d'utilisateurs (pour la pagination)
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les utilisateurs avec pagination
     */
    public function findPaginated(int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('u')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    private function extractSearchTerms(string $searchQuery): array
    {
        $searchQuery = trim(preg_replace('/[[:space:]]+/', ' ', $searchQuery));
        $terms = array_unique(explode(' ', $searchQuery));

        return array_filter($terms, function ($term) {
            return 2 <= mb_strlen($term);
        });
    }
}