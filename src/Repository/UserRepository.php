<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
 * Retourne les utilisateurs ayant au moins un des tags sélectionnés sur la première question taggable.
 * @param array $tagIds
 * @return User[]
 */
public function findByTaggableQuestion1Tags(array $tagIds): array
{
    if (empty($tagIds)) {
        return $this->findAll();
    }

    // Récupérer d'abord les noms de tags correspondant aux IDs
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

    $qb = $this->createQueryBuilder('u')
        ->join('u.userQuestions', 'uq')
        ->where('uq.question = :question')
        ->andWhere('uq.answer IN (:tagNames)')
        ->setParameter('question', 'Taggable Question 0')
        ->setParameter('tagNames', $tagNames)
        ->groupBy('u.id')
        ->having('COUNT(DISTINCT uq.answer) = :nbTags')
        ->setParameter('nbTags', count($tagNames));

    return $qb->getQuery()->getResult();
}

    // /**
    //  * Retourne les utilisateurs ayant au moins un des tags sélectionnés sur la première question taggable.
    //  * @param array $tagNames
    //  * @return User[]
    //  */
    // public function findByTaggableQuestion1Tags(array $tagNames): array
    // {
    //     if (empty($tagNames)) {
    //         return $this->findAll();
    //     }
    //     $qb = $this->createQueryBuilder('u')
    //         ->join('u.userQuestions', 'uq')
    //         ->where('uq.question = :question')
    //         ->andWhere('uq.answer IN (:tagNames)')
    //         ->setParameter('question', 'Taggable Question 0')
    //         ->setParameter('tagNames', $tagNames)
    //         ->groupBy('u.id')
    //         ->having('COUNT(DISTINCT uq.answer) = :nbTags')
    //         ->setParameter('nbTags', count($tagNames));
    //     return $qb->getQuery()->getResult();
    // }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
