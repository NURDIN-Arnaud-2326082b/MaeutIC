<?php

/**
 * Tests unitaires pour TaggableRepository
 *
 * Teste les méthodes du repository des entités taggables :
 * - Recherche par type et ID d'entité
 */

namespace App\Tests\Repository;

use App\Repository\TaggableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class TaggableRepositoryTest extends TestCase
{
    private TaggableRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new TaggableRepository($this->registry);

        $this->assertInstanceOf(TaggableRepository::class, $repository);
    }

    /**
     * Teste la recherche par type et ID d'entité
     */
    public function testFindByTypeAndId(): void
    {
        $type = 'User';
        $id = 1;

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

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
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);

        $result = $this->repository->findByTypeAndId($type, $id);

        $this->assertIsArray($result);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(TaggableRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
