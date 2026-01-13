<?php

/**
 * Tests unitaires pour ArticleRepository
 *
 * Teste les méthodes du repository des articles :
 * - Recherche de tous les articles triés par titre
 * - Recherche d'articles par tags
 * - Constructeur
 */

namespace App\Tests\Repository;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\TaggableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ArticleRepositoryTest extends TestCase
{
    private ArticleRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste la recherche de tous les articles triés par titre
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

    /**
     * Teste la recherche d'articles par tags
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
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new ArticleRepository($this->registry);

        $this->assertInstanceOf(ArticleRepository::class, $repository);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        // Mock du ClassMetadata pour éviter les erreurs d'initialisation Doctrine
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Article::class;

        $this->entityManager->method('getClassMetadata')
            ->with(Article::class)
            ->willReturn($classMetadata);

        $this->repository = $this->getMockBuilder(ArticleRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
