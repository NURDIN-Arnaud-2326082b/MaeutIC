<?php

/**
 * Tests unitaires pour NetworkService
 *
 * Teste les méthodes du service de gestion de réseau d'utilisateurs :
 * - Ajout d'utilisateurs au réseau
 * - Suppression d'utilisateurs du réseau
 * - Vérification d'appartenance au réseau
 * - Récupération du réseau d'un utilisateur
 * - Statistiques du réseau
 */

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\NetworkService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class NetworkServiceTest extends TestCase
{
    private NetworkService $service;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    /**
     * Teste le constructeur du service
     */
    public function test__construct(): void
    {
        $this->assertInstanceOf(NetworkService::class, $this->service);
    }

    /**
     * Teste la récupération des statistiques du réseau
     */
    public function testGetNetworkStats(): void
    {
        $user = $this->createMock(User::class);

        $user->method('getNetwork')
            ->willReturn([2, 3, 4]);

        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => [2, 3, 4]])
            ->willReturn([new User(), new User(), new User()]);

        $stats = $this->service->getNetworkStats($user);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertEquals(3, $stats['total']);
    }

    /**
     * Teste la récupération du réseau d'un utilisateur
     */
    public function testGetUserNetwork(): void
    {
        $user = $this->createMock(User::class);

        $user->method('getNetwork')
            ->willReturn([2, 3]);

        $mockUsers = [new User(), new User()];

        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => [2, 3]])
            ->willReturn($mockUsers);

        $result = $this->service->getUserNetwork($user);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * Teste l'ajout d'un utilisateur au réseau
     */
    public function testAddToNetwork(): void
    {
        $user = $this->createMock(User::class);
        $targetUserId = 5;

        $user->expects($this->once())
            ->method('addToNetwork')
            ->with($targetUserId);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->addToNetwork($user, $targetUserId);

        $this->assertTrue($result);
    }

    /**
     * Teste la suppression d'un utilisateur du réseau
     */
    public function testRemoveFromNetwork(): void
    {
        $user = $this->createMock(User::class);
        $targetUserId = 5;

        $user->expects($this->once())
            ->method('removeFromNetwork')
            ->with($targetUserId);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->removeFromNetwork($user, $targetUserId);

        $this->assertTrue($result);
    }

    /**
     * Teste la vérification d'appartenance au réseau
     */
    public function testIsInNetwork(): void
    {
        $user = $this->createMock(User::class);
        $targetUserId = 5;

        $user->expects($this->once())
            ->method('isInNetwork')
            ->with($targetUserId)
            ->willReturn(true);

        $result = $this->service->isInNetwork($user, $targetUserId);

        $this->assertTrue($result);
    }

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->service = new NetworkService(
            $this->userRepository,
            $this->entityManager
        );
    }
}
