<?php

/**
 * Tests unitaires pour NotificationRepository
 *
 * Teste les méthodes du repository des notifications :
 * - Recherche de notifications en attente par destinataire
 */

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class NotificationRepositoryTest extends TestCase
{
    private NotificationRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste la recherche de notifications en attente par destinataire
     */
    public function testFindPendingByRecipient(): void
    {
        $user = new User();

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('n.createdAt', 'DESC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('n')
            ->willReturn($qb);

        $result = $this->repository->findPendingByRecipient($user);

        $this->assertIsArray($result);
    }

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new NotificationRepository($this->registry);

        $this->assertInstanceOf(NotificationRepository::class, $repository);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(NotificationRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
