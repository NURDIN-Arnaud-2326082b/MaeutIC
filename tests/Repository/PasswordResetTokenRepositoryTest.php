<?php

/**
 * Tests unitaires pour PasswordResetTokenRepository
 *
 * Teste les méthodes du repository des tokens de réinitialisation de mot de passe :
 * - Recherche de tokens valides
 * - Suppression de tokens pour un utilisateur
 * - Comptage des demandes récentes
 * - Nettoyage des tokens expirés
 */

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class PasswordResetTokenRepositoryTest extends TestCase
{
    private PasswordResetTokenRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste la suppression des tokens pour un utilisateur
     */
    public function testRemoveTokensForUser(): void
    {
        $user = new User();

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('delete')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('t.user = :user')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('user', $user)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute');

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);

        $this->repository->removeTokensForUser($user);
    }

    /**
     * Teste le comptage des demandes récentes
     */
    public function testCountRecentRequests(): void
    {
        $user = new User();
        $since = new DateTimeImmutable('-1 hour');

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(t.id)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('t.user = :user')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('t.createdAt >= :since')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(2);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);

        $result = $this->repository->countRecentRequests($user, $since);

        $this->assertEquals(2, $result);
    }

    /**
     * Teste le nettoyage des tokens expirés
     */
    public function testCleanupExpiredTokens(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('delete')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('t.expiresAt < :now')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('now', $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute');

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);

        $this->repository->cleanupExpiredTokens();
    }

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new PasswordResetTokenRepository($this->registry);

        $this->assertInstanceOf(PasswordResetTokenRepository::class, $repository);
    }

    /**
     * Teste la recherche d'un token valide
     */
    public function testFindValidToken(): void
    {
        $token = 'test_token_123';

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('where')
            ->with('t.token = :token')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);

        $result = $this->repository->findValidToken($token);

        $this->assertNull($result);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(PasswordResetTokenRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
