<?php

/**
 * Tests unitaires pour MessageRepository
 *
 * Repository basique pour les messages privés.
 * Teste principalement le constructeur car aucune méthode personnalisée n'est définie.
 */

namespace App\Tests\Repository;

use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class MessageRepositoryTest extends TestCase
{
    private ManagerRegistry $registry;
    private EntityManagerInterface $entityManager;

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new MessageRepository($this->registry);

        $this->assertInstanceOf(MessageRepository::class, $repository);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);
    }
}
