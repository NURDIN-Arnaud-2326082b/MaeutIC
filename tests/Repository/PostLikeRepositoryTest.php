<?php

/**
 * Tests unitaires pour PostLikeRepository
 *
 * Teste les méthodes du repository des likes sur les posts :
 * - Vérification si un post est liké par un utilisateur
 * - Recherche de likes par utilisateur et post
 * - Comptage des likes par post
 */

namespace App\Tests\Repository;

use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\User;
use App\Repository\PostLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class PostLikeRepositoryTest extends TestCase
{
    private PostLikeRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste la vérification si un post est liké par un utilisateur
     */
    public function testIsLikedByUser(): void
    {
        $post = new Post();
        $user = new User();

        $this->repository->expects($this->once())
            ->method('findByUserAndPost')
            ->with($user, $post)
            ->willReturn(new PostLike());

        $result = $this->repository->isLikedByUser($post, $user);

        $this->assertTrue($result);
    }

    /**
     * Teste la recherche de like par utilisateur et post
     */
    public function testFindByUserAndPost(): void
    {
        $user = new User();
        $post = new Post();

        $repository = $this->getMockBuilder(PostLikeRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'user' => $user,
                'post' => $post
            ])
            ->willReturn(new PostLike());

        $result = $repository->findByUserAndPost($user, $post);

        $this->assertInstanceOf(PostLike::class, $result);
    }

    /**
     * Teste le comptage des likes par post
     */
    public function testCountByPost(): void
    {
        $post = new Post();

        $repository = $this->getMockBuilder(PostLikeRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['count'])
            ->getMock();

        $repository->expects($this->once())
            ->method('count')
            ->with(['post' => $post])
            ->willReturn(10);

        $result = $repository->countByPost($post);

        $this->assertEquals(10, $result);
    }

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new PostLikeRepository($this->registry);

        $this->assertInstanceOf(PostLikeRepository::class, $repository);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(PostLikeRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findByUserAndPost', 'count', 'findOneBy'])
            ->getMock();
    }
}
