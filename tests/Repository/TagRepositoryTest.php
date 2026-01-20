<?php

/**
 * Tests unitaires pour TagRepository
 *
 * Teste les méthodes du repository des tags :
 * - Recherche de tous les tags triés par nom
 * - Recherche de tags par nom (LIKE)
 * - Recherche de tags avec autocomplétion
 */

namespace App\Tests\Repository;

use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class TagRepositoryTest extends TestCase
{
    private TagRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new TagRepository($this->registry);

        $this->assertInstanceOf(TagRepository::class, $repository);
    }

    /**
     * Teste la recherche de tags par nom (autocomplétion)
     */
    public function testSearchByName(): void
    {
        $searchQuery = 'php';

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('where')
            ->with('LOWER(t.name) LIKE :q')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('q', '%' . strtolower($searchQuery) . '%')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('t.name', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);

        $result = $this->repository->searchByName($searchQuery);

        $this->assertIsArray($result);
    }

    /**
     * Teste la recherche de tous les tags triés par nom
     */
    public function testFindAllOrderedByName(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('t.name', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);

        $result = $this->repository->findAllOrderedByName();

        $this->assertIsArray($result);
    }

    /**
     * Teste la recherche de tags par nom avec LIKE
     */
    public function testFindByName(): void
    {
        $name = 'symfony';

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('where')
            ->with('t.name LIKE :name')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('name', '%' . $name . '%')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);

        $result = $this->repository->findByName($name);

        $this->assertIsArray($result);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(TagRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
