<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class NetworkApiController extends AbstractController
{
    /**
     * Get network members for a user
     */
    #[Route('/network/{userId}', name: 'api_network_get', methods: ['GET'])]
    public function getNetwork(int $userId, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($userId);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $networkIds = $user->getNetwork();
        $networkMembers = [];

        foreach ($networkIds as $memberId) {
            $member = $userRepository->find($memberId);
            if ($member) {
                $networkMembers[] = [
                    'id' => $member->getId(),
                    'username' => $member->getUsername(),
                    'profileImage' => $member->getProfileImage() ? '/profile_images/' . $member->getProfileImage() : null,
                ];
            }
        }

        return $this->json($networkMembers);
    }

    /**
     * Toggle network status (add/remove from network)
     */
    #[Route('/network/toggle/{userId}', name: 'api_network_toggle', methods: ['POST'])]
    public function toggleNetwork(int $userId, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $targetUser = $userRepository->find($userId);

        if (!$targetUser) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Ne peut pas s'ajouter soi-même
        if ($currentUser->getId() === $userId) {
            return $this->json(['error' => 'Cannot add yourself to network'], Response::HTTP_BAD_REQUEST);
        }

        $isInNetwork = $currentUser->isInNetwork($userId);

        if ($isInNetwork) {
            $currentUser->removeFromNetwork($userId);
            $message = 'Removed from network';
            $inNetwork = false;
        } else {
            $currentUser->addToNetwork($userId);
            $message = 'Added to network';
            $inNetwork = true;
        }

        $entityManager->flush();

        return $this->json([
            'message' => $message,
            'inNetwork' => $inNetwork
        ]);
    }

    /**
     * Toggle block status (block/unblock user)
     */
    #[Route('/block/toggle/{userId}', name: 'api_block_toggle', methods: ['POST'])]
    public function toggleBlock(int $userId, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $targetUser = $userRepository->find($userId);

        if (!$targetUser) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Ne peut pas se bloquer soi-même
        if ($currentUser->getId() === $userId) {
            return $this->json(['error' => 'Cannot block yourself'], Response::HTTP_BAD_REQUEST);
        }

        $isBlocked = $currentUser->isBlocked($userId);

        if ($isBlocked) {
            $currentUser->removeFromBlocked($userId);
            // Retirer aussi du blockedBy de l'autre utilisateur
            $targetUser->removeFromBlockedBy($currentUser->getId());
            $message = 'User unblocked';
            $blocked = false;
        } else {
            $currentUser->addToBlocked($userId);
            // Ajouter aussi au blockedBy de l'autre utilisateur
            $targetUser->addToBlockedBy($currentUser->getId());
            $message = 'User blocked';
            $blocked = true;
        }

        $entityManager->flush();

        return $this->json([
            'message' => $message,
            'blocked' => $blocked
        ]);
    }
}
