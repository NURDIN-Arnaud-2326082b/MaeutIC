<?php

/**
 * Tests unitaires pour AuthorRepository
 *
 * Teste les méthodes du repository des auteurs :
 * - Recherche d'un auteur par ID
 * - Ajout, modification et suppression d'auteurs
 * - Recherche par tags
 * - Recherche de tous les auteurs triés par nom
 * - Constructeur
 */

namespace App\Tests\Repository;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use App\Repository\TaggableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class AuthorRepositoryTest extends TestCase
{
    private AuthorRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste la recherche d'un auteur par ID
     */
    public function testFindById(): void
    {
        $authorId = 1;

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('a.id = :id')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('id', $authorId)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $result = $this->repository->findById($authorId);

        $this->assertNull($result);
    }

    /**
     * Teste la suppression d'un auteur
     */
    public function testRemoveAuthor(): void
    {
        $author = new Author();

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($author);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $repository = new AuthorRepository($this->registry);
        $repository->removeAuthor($author);
    }

    /**
     * Teste la recherche d'auteurs par tags
     */
    public function testFindByTags(): void
    {
        $tagIds = [1, 2, 3];
        $taggableRepository = $this->createMock(TaggableRepository::class);

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
            ->with('a')
            ->willReturn($qb);

        $result = $this->repository->findByTags($tagIds, $taggableRepository);

        $this->assertIsArray($result);
    }

    /**
     * Teste l'ajout d'un auteur
     */
    public function testAddAuthor(): void
    {
        $author = new Author();

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($author);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $repository = new AuthorRepository($this->registry);
        $repository->addAuthor($author);
    }

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new AuthorRepository($this->registry);

        $this->assertInstanceOf(AuthorRepository::class, $repository);
    }

    /**
     * Teste la recherche de tous les auteurs triés par nom
     */
    public function testFindAllOrderedByName(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('a.name', 'ASC')
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

        $result = $this->repository->findAllOrderedByName();

        $this->assertIsArray($result);
    }

    /**
     * Teste la modification d'un auteur
     */
    public function testEditAuthor(): void
    {
        $author = new Author();

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($author);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $repository = new AuthorRepository($this->registry);
        $repository->editAuthor($author);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        // Mock du ClassMetadata pour éviter les erreurs d'initialisation Doctrine
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Author::class;

        $this->entityManager->method('getClassMetadata')
            ->with(Author::class)
            ->willReturn($classMetadata);

        $this->repository = $this->getMockBuilder(AuthorRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
