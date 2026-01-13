<?php

/**
 * Tests unitaires pour ForumRepository
 *
 * Teste les méthodes du repository des forums :
 * - Recherche de tous les forums triés par titre
 */

namespace App\Tests\Repository;

use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ForumRepositoryTest extends TestCase
{
    private ForumRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new ForumRepository($this->registry);

        $this->assertInstanceOf(ForumRepository::class, $repository);
    }

    /**
     * Teste la recherche de tous les forums triés par titre
     */
    public function testFindAllOrderedByTitle(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('f.title', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($qb);

        $result = $this->repository->findAllOrderedByTitle();

        $this->assertIsArray($result);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(ForumRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
