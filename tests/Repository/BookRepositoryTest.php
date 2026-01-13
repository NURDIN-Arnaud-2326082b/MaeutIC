<?php

/**
 * Tests unitaires pour BookRepository
 *
 * Teste les méthodes du repository des livres :
 * - Recherche de livres par tags
 * - Recherche de tous les livres triés par titre
 * - Constructeur
 */

namespace App\Tests\Repository;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class BookRepositoryTest extends TestCase
{
    private BookRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste la recherche de livres par tags
     */
    public function testFindByTags(): void
    {
        $tagIds = [1, 2, 3];

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->method('innerJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('having')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturn($qb);

        $result = $this->repository->findByTags($tagIds);

        $this->assertIsArray($result);
    }

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new BookRepository($this->registry);

        $this->assertInstanceOf(BookRepository::class, $repository);
    }

    /**
     * Teste la recherche de tous les livres triés par titre
     */
    public function testFindAllOrderedByTitle(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('a.title', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
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

        // Mock du ClassMetadata pour éviter les erreurs d'initialisation Doctrine
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Book::class;

        $this->entityManager->method('getClassMetadata')
            ->with(Book::class)
            ->willReturn($classMetadata);

        $this->repository = $this->getMockBuilder(BookRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
