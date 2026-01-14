<?php

namespace App\Repository;

use App\Entity\UserQuestions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserQuestionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserQuestions::class);
    }

    /**
     * Trouve toutes les questions d'un utilisateur donné.
     *
     * @param int $userId
     * @return UserQuestions[]
     */
    public function findAllByUser(int $userId): array
    {
        return $this->createQueryBuilder('uq')
            ->andWhere('uq.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une UserQuestions par user, question et answer.
     *
     * @param $user
     * @param string $question
     * @param string $answer
     * @return UserQuestions|null
     */
    public function findOneByUserQuestionAnswer($user, string $question, string $answer): ?UserQuestions
    {
        return $this->findOneBy([
            'user' => $user,
            'question' => $question,
            'answer' => $answer
        ]);
    }
}