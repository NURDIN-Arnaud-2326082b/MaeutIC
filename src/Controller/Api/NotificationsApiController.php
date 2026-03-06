<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Conversation;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class NotificationsApiController extends AbstractController
{
    /**
     * Get all notifications for the current user
     */
    #[Route('/notifications', name: 'api_notifications_list', methods: ['GET'])]
    public function getNotifications(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $notifRepo = $entityManager->getRepository(Notification::class);
        $notifications = $notifRepo->findBy(['recipient' => $currentUser], ['createdAt' => 'DESC']);

        $out = [];
        $unread = 0;
        foreach ($notifications as $n) {
            $sender = $n->getSender();
            $data = $n->getData();
            $out[] = [
                'id' => $n->getId(),
                'type' => $n->getType(),
                'data' => $data,
                'status' => $n->getStatus(),
                'isRead' => $n->isRead(),
                'sender' => $sender ? [
                    'id' => $sender->getId(),
                    'username' => $sender->getUsername(),
                    'profileImage' => $sender->getProfileImage() 
                        ? '/profile_images/' . $sender->getProfileImage() 
                        : null
                ] : null,
                'createdAt' => $n->getCreatedAt()->format(DateTime::ATOM),
            ];
            if (!$n->isRead()) $unread++;
        }

        return $this->json([
            'notifications' => $out,
            'count' => count($out),
            'unread' => $unread
        ]);
    }

    /**
     * Accept a network request notification
     */
    #[Route('/notifications/accept/{id}', name: 'api_notifications_accept', methods: ['POST'])]
    public function acceptNotification(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $notifRepo = $entityManager->getRepository(Notification::class);
        $notification = $notifRepo->find($id);

        if (!$notification) {
            return $this->json(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }

        if ($notification->getRecipient()->getId() !== $currentUser->getId()) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($notification->getType() !== 'network_request' || $notification->getStatus() !== 'pending') {
            return $this->json(['error' => 'Invalid notification'], Response::HTTP_BAD_REQUEST);
        }

        $sender = $notification->getSender();
        if (!$sender) {
            $entityManager->remove($notification);
            $entityManager->flush();
            return $this->json(['success' => true]);
        }

        // Create mutual connection
        if (!$currentUser->isInNetwork($sender->getId())) {
            $currentUser->addToNetwork($sender->getId());
        }
        if (!$sender->isInNetwork($currentUser->getId())) {
            $sender->addToNetwork($currentUser->getId());
        }

        // Remove notification
        $entityManager->remove($notification);

        // Create conversation if necessary
        $convRepo = $entityManager->getRepository(Conversation::class);
        $qb = $convRepo->createQueryBuilder('c');
        $qb->where('(c.user1 = :a AND c.user2 = :b) OR (c.user1 = :b AND c.user2 = :a)')
            ->setParameter('a', $currentUser)
            ->setParameter('b', $sender)
            ->setMaxResults(1);
        $conv = $qb->getQuery()->getOneOrNullResult();

        if (!$conv) {
            $conv = new Conversation();
            if (method_exists($conv, 'setUser1') && method_exists($conv, 'setUser2')) {
                $conv->setUser1($currentUser);
                $conv->setUser2($sender);
            }
            $entityManager->persist($conv);
        }

        $entityManager->persist($currentUser);
        $entityManager->persist($sender);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'accepted' => true,
            'conversationId' => $conv->getId() ?? null
        ]);
    }

    /**
     * Decline a network request notification
     */
    #[Route('/notifications/decline/{id}', name: 'api_notifications_decline', methods: ['POST'])]
    public function declineNotification(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $notifRepo = $entityManager->getRepository(Notification::class);
        $notification = $notifRepo->find($id);

        if (!$notification) {
            return $this->json(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }

        if ($notification->getRecipient()->getId() !== $currentUser->getId()) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        return $this->json(['success' => true, 'declined' => true]);
    }

    /**
     * Clear all notifications for current user
     */
    #[Route('/notifications/clear-all', name: 'api_notifications_clear_all', methods: ['POST'])]
    public function clearAllNotifications(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $notifRepo = $entityManager->getRepository(Notification::class);
        $notifications = $notifRepo->findBy(['recipient' => $currentUser]);

        foreach ($notifications as $n) {
            $entityManager->remove($n);
        }
        $entityManager->flush();

        return $this->json(['success' => true, 'cleared' => true]);
    }
}
