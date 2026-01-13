<?php

/**
 * Tests unitaires pour ResourceRepository
 *
 * Teste les méthodes du repository des ressources :
 * - Recherche de ressources par page avec offset
 */

namespace App\Tests\Repository;

use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ResourceRepositoryTest extends TestCase
{
    private ResourceRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new ResourceRepository($this->registry);

        $this->assertInstanceOf(ResourceRepository::class, $repository);
    }

    /**
     * Teste la recherche de ressources par page avec offset
     */
    public function testFindByPage(): void
    {
        $page = 'library';
        $offset = 10;

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('r.page = :page')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('page', $page)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with($offset)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($qb);

        $result = $this->repository->findByPage($page, $offset);

        $this->assertIsArray($result);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(ResourceRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
