<?php

/**
 * Tests unitaires pour CommentRepository
 *
 * Teste les méthodes du repository des commentaires :
 * - Recherche de commentaires par post
 * - Ajout de commentaires
 */

namespace App\Tests\Repository;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class CommentRepositoryTest extends TestCase
{
    private CommentRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new CommentRepository($this->registry);

        $this->assertInstanceOf(CommentRepository::class, $repository);
    }

    /**
     * Teste la recherche de commentaires par post
     */
    public function testFindByPost(): void
    {
        $postId = 1;

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('innerJoin')
            ->with('c.post', 'p')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('p.id = :postId')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('postId', $postId)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('c.creationDate', 'DESC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($qb);

        $result = $this->repository->findByPost($postId);

        $this->assertIsArray($result);
    }

    /**
     * Teste l'ajout d'un commentaire
     */
    public function testAddComment(): void
    {
        $body = 'Test comment';
        $post = $this->createMock(Post::class);
        $user = $this->createMock(User::class);

        // Mock du ClassMetadata pour éviter les erreurs d'initialisation Doctrine
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Comment::class;

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Comment::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(Comment::class)
            ->willReturn($classMetadata);

        $repository = new CommentRepository($this->registry);
        $repository->addComment($body, $post, $user);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        // Mock du ClassMetadata pour éviter les erreurs d'initialisation Doctrine
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Comment::class;

        $this->entityManager->method('getClassMetadata')
            ->with(Comment::class)
            ->willReturn($classMetadata);

        $this->repository = $this->getMockBuilder(CommentRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
