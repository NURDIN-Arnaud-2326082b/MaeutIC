<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class NetworkService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function getUserNetwork(User $user): array
    {
        $networkIds = $user->getNetwork();
        
        if (empty($networkIds)) {
            return [];
        }

        return $this->userRepository->findBy(['id' => $networkIds]);
    }

    public function addToNetwork(User $user, int $targetUserId): bool
    {
        try {
            $user->addToNetwork($targetUserId);
            $this->entityManager->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function removeFromNetwork(User $user, int $targetUserId): bool
    {
        try {
            $user->removeFromNetwork($targetUserId);
            $this->entityManager->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isInNetwork(User $user, int $targetUserId): bool
    {
        return $user->isInNetwork($targetUserId);
    }

    public function getNetworkStats(User $user): array
    {
        $networkUsers = $this->getUserNetwork($user);

        return [
            'total' => count($networkUsers),
        ];
    }
}