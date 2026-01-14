<?php

/**
 * Tests unitaires pour UserQuestionsRepository
 *
 * Teste les méthodes du repository des réponses aux questions utilisateurs :
 * - Recherche par utilisateur, question et réponse
 * - Recherche de toutes les questions d'un utilisateur
 */

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserQuestionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use stdClass;

class UserQuestionsRepositoryTest extends TestCase
{
    private UserQuestionsRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste la recherche d'une réponse utilisateur par user, question et answer
     */
    public function testFindOneByUserQuestionAnswer(): void
    {
        $user = new User();
        $question = 'What is your favorite color?';
        $answer = 'Blue';

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'user' => $user,
                'question' => $question,
                'answer' => $answer
            ])
            ->willReturn(new stdClass());

        $result = $this->repository->findOneByUserQuestionAnswer($user, $question, $answer);

        $this->assertIsObject($result);
    }

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new UserQuestionsRepository($this->registry);

        $this->assertInstanceOf(UserQuestionsRepository::class, $repository);
    }

    /**
     * Teste la recherche de toutes les questions d'un utilisateur
     */
    public function testFindAllByUser(): void
    {
        $userId = 1;

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('uq.user = :userId')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('uq')
            ->willReturn($qb);

        $result = $this->repository->findAllByUser($userId);

        $this->assertIsArray($result);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(UserQuestionsRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder', 'findOneBy'])
            ->getMock();
    }
}
