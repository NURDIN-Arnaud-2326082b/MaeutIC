<?php

/**
 * Tests unitaires pour UserLikeRepository
 *
 * Teste les méthodes du repository des likes sur les commentaires :
 * - Vérification si un utilisateur a liké un commentaire
 * - Comptage des likes par commentaire
 * - Ajout et suppression de likes
 */

namespace App\Tests\Repository;

use App\Entity\UserLike;
use App\Repository\UserLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class UserLikeRepositoryTest extends TestCase
{
    private UserLikeRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste la vérification si un utilisateur a liké un commentaire
     */
    public function testHasUserLikedComment(): void
    {
        $userId = 1;
        $commentId = 2;

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(ul.id)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('ul.user = :userId')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('ul.comment = :commentId')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(1);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('ul')
            ->willReturn($qb);

        $result = $this->repository->hasUserLikedComment($userId, $commentId);

        $this->assertTrue($result);
    }

    /**
     * Teste le comptage des likes par commentaire
     */
    public function testCountByCommentId(): void
    {
        $commentId = 2;

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(ul.id)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('ul.comment = :commentId')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('commentId', $commentId)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(5);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('ul')
            ->willReturn($qb);

        $result = $this->repository->countByCommentId($commentId);

        $this->assertEquals(5, $result);
    }

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new UserLikeRepository($this->registry);

        $this->assertInstanceOf(UserLikeRepository::class, $repository);
    }

    /**
     * Teste l'ajout d'un like
     */
    public function testAdd(): void
    {
        $userLike = new UserLike();

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($userLike);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $repository = new UserLikeRepository($this->registry);
        $repository->add($userLike, true);
    }

    /**
     * Teste la suppression d'un like
     */
    public function testRemove(): void
    {
        $userLike = new UserLike();

        // Mock du ClassMetadata pour éviter les erreurs d'initialisation Doctrine
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = UserLike::class;

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($userLike);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(UserLike::class)
            ->willReturn($classMetadata);

        $repository = new UserLikeRepository($this->registry);
        $repository->remove($userLike, true);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        // Mock du ClassMetadata pour éviter les erreurs d'initialisation Doctrine
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = UserLike::class;

        $this->entityManager->method('getClassMetadata')
            ->with(UserLike::class)
            ->willReturn($classMetadata);

        $this->repository = $this->getMockBuilder(UserLikeRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }
}
