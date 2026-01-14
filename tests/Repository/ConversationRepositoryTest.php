<?php

/**
 * Tests unitaires pour ConversationRepository
 *
 * Repository basique pour les conversations.
 * Teste principalement le constructeur car aucune méthode personnalisée n'est définie.
 */

namespace App\Tests\Repository;

use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ConversationRepositoryTest extends TestCase
{
    private ManagerRegistry $registry;
    private EntityManagerInterface $entityManager;

    /**
     * Teste le constructeur du repository
     */
    public function test__construct(): void
    {
        $repository = new ConversationRepository($this->registry);

        $this->assertInstanceOf(ConversationRepository::class, $repository);
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);
    }
}
