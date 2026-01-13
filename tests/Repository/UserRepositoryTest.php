<?php

/**
 * Tests unitaires pour UserRepository
 *
 * Teste les méthodes personnalisées du repository des utilisateurs :
 * - Mise à jour des mots de passe
 * - Recherche d'utilisateurs non-amis
 * - Pagination des utilisateurs
 * - Recherche par requête
 * - Recherche par tags de questions taggables
 * - Comptage total des utilisateurs
 */

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    /**
     * Teste la mise à jour du mot de passe d'un utilisateur
     */
    public function testUpgradePassword(): void
    {
        $user = new User();
        $newPassword = 'new_hashed_password';

        // Mock du ClassMetadata pour éviter les erreurs d'initialisation Doctrine
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = User::class;

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(User::class)
            ->willReturn($classMetadata);

        $repository = new UserRepository($this->registry);
        $repository->upgradePassword($user, $newPassword);

        $this->assertEquals($newPassword, $user->getPassword());
    }

    /**
     * Teste que upgradePassword lève une exception pour un utilisateur non supporté
     */
    public function testUpgradePasswordWithUnsupportedUser(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $invalidUser = $this->createMock(PasswordAuthenticatedUserInterface::class);

        $repository = new UserRepository($this->registry);
        $repository->upgradePassword($invalidUser, 'password');
    }

    /**
     * Teste la recherche d'utilisateurs non-amis pour les recommandations
     */
    public function testFindNonFriendUsers(): void
    {
        $currentUser = $this->createMock(User::class);
        $currentUser->method('getId')->willReturn(1);
        $friendIds = [2, 3, 4];

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('where')
            ->with('u.id != :currentUserId')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('u.id NOT IN (:friendIds)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(500)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($qb);

        $result = $this->repository->findNonFriendUsers($currentUser, $friendIds);

        $this->assertIsArray($result);
    }

    /**
     * Teste la pagination des utilisateurs
     */
    public function testFindPaginated(): void
    {
        $page = 2;
        $limit = 20;

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with(($page - 1) * $limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($qb);

        $result = $this->repository->findPaginated($page, $limit);

        $this->assertIsArray($result);
    }

    /**
     * Teste la recherche d'utilisateurs par requête de recherche
     */
    public function testFindBySearchQuery(): void
    {
        $query = 'john doe';

        $qb = $this->createMock(QueryBuilder::class);
        $queryMock = $this->createMock(Query::class);

        $qb->method('orWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($queryMock);

        $queryMock->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($qb);

        $result = $this->repository->findBySearchQuery($query);

        $this->assertIsArray($result);
    }

    /**
     * Teste la recherche par tags de questions taggables
     */
    public function testFindByTaggableQuestion1Tags(): void
    {
        $tagIds = [1, 2, 3];

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // Configuration pour la requête des noms de tags
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('having')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $query->method('getResult')->willReturn([
            ['name' => 'Tag1'],
            ['name' => 'Tag2'],
            ['name' => 'Tag3']
        ]);

        // Mock de l'EntityManager pour createQueryBuilder
        $this->entityManager->method('createQueryBuilder')
            ->willReturn($qb);

        // Mock du repository pour createQueryBuilder
        $this->repository->method('createQueryBuilder')
            ->willReturn($qb);

        $result = $this->repository->findByTaggableQuestion1Tags($tagIds);

        $this->assertIsArray($result);
    }

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new UserRepository($this->registry);

        $this->assertInstanceOf(UserRepository::class, $repository);
    }

    /**
     * Teste le comptage total des utilisateurs
     */
    public function testCountAll(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(u.id)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(42);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($qb);

        $result = $this->repository->countAll();

        $this->assertEquals(42, $result);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        // Mock du ClassMetadata pour éviter les erreurs d'initialisation Doctrine
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = User::class;

        $this->entityManager->method('getClassMetadata')
            ->with(User::class)
            ->willReturn($classMetadata);

        $this->repository = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder', 'findAll', 'findBy'])
            ->getMock();
    }
}
