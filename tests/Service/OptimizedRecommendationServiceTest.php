<?php

/**
 * Tests unitaires pour OptimizedRecommendationService
 *
 * Teste les méthodes du service de recommandations d'utilisateurs :
 * - Calcul des scores de recommandation
 * - Nettoyage du cache utilisateur
 * - Application de l'algorithme de recommandation comportementale
 */

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\OptimizedRecommendationService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\CacheInterface;

class OptimizedRecommendationServiceTest extends TestCase
{
    private OptimizedRecommendationService $service;
    private UserRepository $userRepository;
    private PostRepository $postRepository;
    private EntityManagerInterface $entityManager;
    private CacheInterface $cache;
    private Security $security;
    private LoggerInterface $logger;

    /**
     * Teste le nettoyage du cache utilisateur
     */
    public function testClearUserCache(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $this->cache->expects($this->atLeastOnce())
            ->method('delete')
            ->willReturn(true);

        $this->service->clearUserCache($user);

        // Si aucune exception n'est levée, le test passe
        $this->assertTrue(true);
    }

    /**
     * Teste le constructeur du service
     */
    public function test__construct(): void
    {
        $this->assertInstanceOf(OptimizedRecommendationService::class, $this->service);
    }

    /**
     * Teste le calcul des scores de recommandation sans utilisateur connecté
     */
    public function testCalculateRecommendationScoresWithoutUser(): void
    {
        $this->security->method('getUser')
            ->willReturn(null);

        $result = $this->service->calculateRecommendationScores();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Teste le calcul des scores de recommandation avec utilisateur connecté
     */
    public function testCalculateRecommendationScores(): void
    {
        $currentUser = $this->createMock(User::class);
        $currentUser->method('getId')->willReturn(1);
        $currentUser->method('getNetwork')->willReturn([2]);

        $otherUser = $this->createMock(User::class);
        $otherUser->method('getId')->willReturn(3);

        $this->userRepository->method('findAll')
            ->willReturn([$currentUser, $otherUser]);

        // Mock de la connexion pour getBehavioralData
        $connection = $this->createMock(Connection::class);
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);

        $connection->method('prepare')->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchOne')->willReturn(0);
        $result->method('fetchAllAssociative')->willReturn([]);

        $this->entityManager->method('getConnection')->willReturn($connection);
        $this->entityManager->method('createQuery')->willReturnCallback(function () {
            $query = $this->createMock(Query::class);
            $query->method('setParameter')->willReturnSelf();
            $query->method('getResult')->willReturn([]);
            return $query;
        });

        // Configure le logger pour ne pas lever d'erreurs
        $this->logger->method('info');

        $scores = $this->service->calculateRecommendationScores($currentUser, 10);

        $this->assertIsArray($scores);
    }

    /**
     * Teste que le service retourne un tableau vide quand tous les utilisateurs sont amis
     */
    public function testCalculateRecommendationScoresAllFriends(): void
    {
        $currentUser = $this->createMock(User::class);
        $currentUser->method('getId')->willReturn(1);
        $currentUser->method('getNetwork')->willReturn([2, 3, 4]);

        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        $user3 = $this->createMock(User::class);
        $user3->method('getId')->willReturn(3);

        $user4 = $this->createMock(User::class);
        $user4->method('getId')->willReturn(4);

        $this->userRepository->method('findAll')
            ->willReturn([$currentUser, $user2, $user3, $user4]);

        // Mock de la connexion pour getBehavioralData
        $connection = $this->createMock(Connection::class);
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);

        $connection->method('prepare')->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchOne')->willReturn(0);
        $result->method('fetchAllAssociative')->willReturn([]);

        $this->entityManager->method('getConnection')->willReturn($connection);
        $this->entityManager->method('createQuery')->willReturnCallback(function () {
            $query = $this->createMock(Query::class);
            $query->method('setParameter')->willReturnSelf();
            $query->method('getResult')->willReturn([]);
            return $query;
        });

        $this->logger->method('info');

        $scores = $this->service->calculateRecommendationScores($currentUser, 10);

        $this->assertIsArray($scores);
        $this->assertEmpty($scores);
    }

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->postRepository = $this->createMock(PostRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new OptimizedRecommendationService(
            $this->userRepository,
            $this->postRepository,
            $this->entityManager,
            $this->cache,
            $this->security,
            $this->logger
        );
    }
}
