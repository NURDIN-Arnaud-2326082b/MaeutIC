<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Notification;
use App\Entity\Conversation;
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
     * Get network status with a user
     */
    #[Route('/network/status/{userId}', name: 'api_network_status', methods: ['GET'])]
    public function getNetworkStatus(int $userId, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if ($currentUser->getId() === $userId) {
            return $this->json(['status' => 'self']);
        }

        $targetUser = $userRepository->find($userId);

        if (!$targetUser) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($currentUser->isInNetwork($targetUser->getId())) {
            $status = 'connected';
        } else {
            $notifRepo = $entityManager->getRepository(Notification::class);
            $outgoing = $notifRepo->findOneBy([
                'sender' => $currentUser,
                'recipient' => $targetUser,
                'type' => 'network_request',
                'status' => 'pending'
            ]);
            if ($outgoing) {
                $status = 'outgoing_request';
            } else {
                $incoming = $notifRepo->findOneBy([
                    'sender' => $targetUser,
                    'recipient' => $currentUser,
                    'type' => 'network_request',
                    'status' => 'pending'
                ]);
                $status = $incoming ? 'incoming_request' : 'none';
            }
        }

        return $this->json([
            'status' => $status,
            'blockedByMe' => $currentUser->isBlocked($targetUser->getId()),
            'blockedByThem' => $targetUser->isBlocked($currentUser->getId())
        ]);
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

        // Check for blocking
        if ($currentUser->isBlocked($targetUser->getId()) || $targetUser->isBlocked($currentUser->getId())) {
            return $this->json(['error' => 'Blocked'], Response::HTTP_FORBIDDEN);
        }

        $notifRepo = $entityManager->getRepository(Notification::class);

        // 1) If already in network -> remove mutual connection
        if ($currentUser->isInNetwork($targetUser->getId())) {
            $currentUser->removeFromNetwork($targetUser->getId());
            $targetUser->removeFromNetwork($currentUser->getId());
            $entityManager->persist($currentUser);
            $entityManager->persist($targetUser);
            $entityManager->flush();

            return $this->json(['success' => true, 'removed' => true]);
        }

        // 2) If outgoing request exists -> cancel it
        $outgoing = $notifRepo->findOneBy([
            'sender' => $currentUser,
            'recipient' => $targetUser,
            'type' => 'network_request',
            'status' => 'pending'
        ]);
        if ($outgoing) {
            $entityManager->remove($outgoing);
            $entityManager->flush();
            return $this->json(['success' => true, 'cancelled' => true]);
        }

        // 3) If incoming request exists -> accept it
        $incoming = $notifRepo->findOneBy([
            'sender' => $targetUser,
            'recipient' => $currentUser,
            'type' => 'network_request',
            'status' => 'pending'
        ]);
        if ($incoming) {
            // Create mutual connection
            $currentUser->addToNetwork($targetUser->getId());
            $targetUser->addToNetwork($currentUser->getId());

            // Remove notification
            $entityManager->remove($incoming);

            // Create conversation if necessary
            $convRepo = $entityManager->getRepository(Conversation::class);
            $qb = $convRepo->createQueryBuilder('c');
            $qb->where('(c.user1 = :a AND c.user2 = :b) OR (c.user1 = :b AND c.user2 = :a)')
                ->setParameter('a', $currentUser)
                ->setParameter('b', $targetUser)
                ->setMaxResults(1);
            $conv = $qb->getQuery()->getOneOrNullResult();

            if (!$conv) {
                $conv = new Conversation();
                if (method_exists($conv, 'setUser1') && method_exists($conv, 'setUser2')) {
                    $conv->setUser1($currentUser);
                    $conv->setUser2($targetUser);
                }
                $entityManager->persist($conv);
            }

            $entityManager->persist($currentUser);
            $entityManager->persist($targetUser);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'accepted' => true,
                'conversationId' => $conv->getId() ?? null
            ]);
        }

        // 4) Otherwise: create a network request notification
        $notification = new Notification();
        $notification->setType('network_request');
        $notification->setSender($currentUser);
        $notification->setRecipient($targetUser);
        $notification->setStatus('pending');
        $notification->setData(['message' => sprintf('%s souhaite rejoindre votre réseau', $currentUser->getUsername() ?? 'Quelqu\'un')]);

        $entityManager->persist($notification);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'pending' => true
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
