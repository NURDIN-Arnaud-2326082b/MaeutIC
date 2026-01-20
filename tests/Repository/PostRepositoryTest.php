<?php

/**
 * Tests unitaires pour PostRepository
 *
 * Teste les méthodes du repository des posts :
 * - Ajout et suppression de posts
 * - Recherche de posts par forum
 * - Recherche de posts par utilisateur
 * - Recherche avancée avec filtres
 * - Tri par nom, date, popularité
 */

namespace App\Tests\Repository;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class PostRepositoryTest extends TestCase
{
    private PostRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste la suppression d'un post
     */
    public function testRemovePost(): void
    {
        $post = new Post();

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($post);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $repository = new PostRepository($this->registry);
        $repository->removePost($post);
    }

    /**
     * Teste la recherche de tous les posts triés par nom
     */
    public function testFindAllOrderedByName(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.name', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->repository->findAllOrderedByName();

        $this->assertIsArray($result);
    }

    /**
     * Teste la recherche de posts par forums méthodologiques
     */
    public function testFindByMethodologyForums(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('join')
            ->with('p.forum', 'f')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('f.special = :special')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('special', 'methodology')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->repository->findByMethodologyForums();

        $this->assertIsArray($result);
    }

    /**
     * Teste la recherche de posts avec des critères avancés
     */
    public function testSearchPosts(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->repository->searchPosts('test', 'all', 'all', 'recent', 'General');

        $this->assertIsArray($result);
    }

    /**
     * Teste la recherche de posts spéciaux (méthodologie, administratif, détente)
     */
    public function testSearchSpecialPosts(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->repository->searchSpecialPosts('test', 'all', 'all', 'recent', 'methodology');

        $this->assertIsArray($result);
    }

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new PostRepository($this->registry);

        $this->assertInstanceOf(PostRepository::class, $repository);
    }

    /**
     * Teste la recherche de réponses par utilisateur
     */
    public function testFindRepliesByUser(): void
    {
        $user = new User();

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('where')
            ->with('p.user = :user')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.isReply = :isReply')
            ->willReturnSelf();

        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->repository->findRepliesByUser($user);

        $this->assertIsArray($result);
    }

    /**
     * Teste la recherche de posts par forum
     */
    public function testFindByForum(): void
    {
        $forumName = 'General';

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('innerJoin')
            ->with('p.forum', 'f')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('f.title = :forumName')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('forumName', $forumName)
            ->willReturnSelf();

        $qb->method('orderBy')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $result = $this->repository->findByForum($forumName);

        $this->assertIsArray($result);
    }

    /**
     * Teste l'ajout d'un post
     */
    public function testAddPost(): void
    {
        $post = new Post();

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($post);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $repository = new PostRepository($this->registry);
        $repository->addPost($post);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        // Mock du ClassMetadata pour éviter les erreurs d'initialisation Doctrine
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Post::class;

        $this->entityManager->method('getClassMetadata')
            ->with(Post::class)
            ->willReturn($classMetadata);

        $this->repository = $this->getMockBuilder(PostRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
