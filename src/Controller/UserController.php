<?php

/**
 * Contrôleur de gestion des utilisateurs et du réseau social
 *
 * Ce contrôleur gère toutes les interactions sociales entre utilisateurs :
 * - Gestion du réseau/connexions
 * - Blocage et déblocage d'utilisateurs
 * - Notifications de demandes de connexion
 * - Consultation du réseau d'un utilisateur
 * - Respect des règles de blocage dans toutes les interactions
 */

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Notification;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class UserController extends AbstractController
{
    /**
     * Liste le réseau d'un utilisateur donné
     *
     * @param int $userId ID de l'utilisateur dont on veut le réseau
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return JsonResponse La réponse JSON contenant le réseau de l'utilisateur
     */
    #[Route('/network/list/{userId}', name: 'network_list', methods: ['GET'])]
    public function listNetwork(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $userRepo = $entityManager->getRepository(User::class);
            $target = $userRepo->find($userId);
            if (!$target) {
                return $this->json(['error' => 'User not found'], 404);
            }

            // Si l'utilisateur courant est bloqué par / bloque la cible, ne pas divulguer son réseau
            $me = $this->getUser();
            if ($me && $me->getId() !== $userId) {
                if ($me->isBlocked($userId) || $target->isBlocked($me->getId()) || $me->isBlockedBy($userId) || $target->isBlockedBy($me->getId())) {
                    return $this->json(['connections' => []]);
                }
            }

            $ids = $target->getNetwork();
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                $ids = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
            }

            if (empty($ids) || !is_array($ids)) {
                return $this->json(['connections' => []]);
            }

            $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
            if (empty($ids)) {
                return $this->json(['connections' => []]);
            }

            $users = $userRepo->findBy(['id' => $ids]);
            $map = [];
            foreach ($users as $u) $map[$u->getId()] = $u;

            $connections = [];
            foreach ($ids as $id) {
                if (!isset($map[$id])) continue;
                $other = $map[$id];

                // Si visiteur connecté, ne pas inclure dans la liste les utilisateurs en situation de blocage mutuel/unilatéral avec le visiteur
                if ($me && $me->getId() !== $userId) {
                    if ($me->isBlocked($other->getId()) || $other->isBlocked($me->getId()) || $me->isBlockedBy($other->getId()) || $other->isBlockedBy($me->getId())) {
                        continue;
                    }
                }

                $connections[] = [
                    'id' => $other->getId(),
                    'username' => $other->getUsername(),
                    'firstName' => method_exists($other, 'getFirstName') ? $other->getFirstName() : null,
                    'lastName' => method_exists($other, 'getLastName') ? $other->getLastName() : null,
                    'profileImage' => method_exists($other, 'getProfileImage') ? $other->getProfileImage() : null,
                ];
            }

            return $this->json(['connections' => $connections]);
        } catch (Throwable $e) {
            return $this->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Vérifie le statut de connexion entre l'utilisateur courant et un autre utilisateur
     *
     * @param int $userId ID de l'autre utilisateur
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return JsonResponse La réponse JSON contenant le statut de connexion
     */
    #[Route('/network/status/{userId}', name: 'network_status', methods: ['GET'])]
    public function networkStatus(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        $me = $this->getUser();
        if (!$me) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        if ($me->getId() === (int)$userId) {
            return $this->json(['status' => 'self']);
        }

        $userRepo = $entityManager->getRepository(User::class);
        $other = $userRepo->find($userId);
        if (!$other) {
            return $this->json(['error' => 'User not found'], 404);
        }

        if ($me->isInNetwork($other->getId())) {
            $status = 'connected';
        } else {
            $notifRepo = $entityManager->getRepository(Notification::class);
            $out = $notifRepo->findOneBy([
                'sender' => $me,
                'recipient' => $other,
                'type' => 'network_request',
                'status' => 'pending'
            ]);
            if ($out) {
                $status = 'outgoing_request';
            } else {
                $in = $notifRepo->findOneBy([
                    'sender' => $other,
                    'recipient' => $me,
                    'type' => 'network_request',
                    'status' => 'pending'
                ]);
                $status = $in ? 'incoming_request' : 'none';
            }
        }

        return $this->json([
            'status' => $status,
            'blockedByMe' => $me->isBlocked($other->getId()),
            'blockedByThem' => $other->isBlocked($me->getId())
        ]);
    }

    /**
     * Bloque ou débloque un utilisateur
     *
     * @param int $userId ID de l'utilisateur à bloquer/débloquer
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return JsonResponse La réponse JSON indiquant le résultat de l'opération
     */
    #[Route('/user/block/toggle/{userId}', name: 'user_block_toggle', methods: ['POST'])]
    public function toggleBlock(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $me = $this->getUser();
            if (!$me) {
                return $this->json(['error' => 'Unauthorized'], 401);
            }
            if ($me->getId() === (int)$userId) {
                return $this->json(['error' => 'Cannot block yourself'], 400);
            }

            $userRepo = $entityManager->getRepository(User::class);
            $other = $userRepo->find($userId);
            if (!$other) {
                return $this->json(['error' => 'User not found'], 404);
            }

            // Si déjà bloqué -> débloquer : retirer côté blocker et côté bloqué (blockedBy)
            if ($me->isBlocked($other->getId())) {
                $me->removeFromBlocked($other->getId());
                // retirer la trace côté "blockedBy" de l'autre utilisateur
                if (method_exists($other, 'removeFromBlockedBy')) {
                    $other->removeFromBlockedBy($me->getId());
                }
                $entityManager->persist($me);
                $entityManager->persist($other);
                $entityManager->flush();
                return $this->json(['success' => true, 'cancelled' => true]);
            }

            // Bloquer : ajouter à blocked du courant et ajouter à blockedBy de l'autre
            $me->addToBlocked($other->getId());
            if (method_exists($other, 'addToBlockedBy')) {
                $other->addToBlockedBy($me->getId());
            }

            if ($me->isInNetwork($other->getId())) $me->removeFromNetwork($other->getId());
            if ($other->isInNetwork($me->getId())) $other->removeFromNetwork($me->getId());

            $notifRepo = $entityManager->getRepository(Notification::class);
            $qb = $notifRepo->createQueryBuilder('n');
            $qb->where('(n.sender = :a AND n.recipient = :b) OR (n.sender = :b AND n.recipient = :a)')
                ->andWhere('n.type = :t')->andWhere('n.status = :s')
                ->setParameter('a', $me)
                ->setParameter('b', $other)
                ->setParameter('t', 'network_request')
                ->setParameter('s', 'pending');
            $pending = $qb->getQuery()->getResult();
            foreach ($pending as $p) $entityManager->remove($p);

            $convRepo = $entityManager->getRepository(Conversation::class);
            $qb2 = $convRepo->createQueryBuilder('c');
            $qb2->where('(c.user1 = :a AND c.user2 = :b) OR (c.user1 = :b AND c.user2 = :a)')
                ->setParameter('a', $me)
                ->setParameter('b', $other);
            $conversations = $qb2->getQuery()->getResult();
            foreach ($conversations as $conv) $entityManager->remove($conv);

            $entityManager->persist($me);
            $entityManager->persist($other);
            $entityManager->flush();

            return $this->json(['success' => true, 'blocked' => true]);
        } catch (Throwable $e) {
            return $this->json(['error' => 'Exception', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Affiche la page des paramètres utilisateur, y compris la liste des utilisateurs bloqués
     *
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return Response La réponse HTTP avec la page des paramètres
     */
    #[Route('/settings', name: 'app_settings', methods: ['GET'])]
    public function settings(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $blockedIds = $user->getBlocked() ?? [];
        $blockedIds = array_values(array_filter(array_map('intval', (array)$blockedIds), fn($v) => $v > 0));

        $blockedUsers = [];
        if (!empty($blockedIds)) {
            $userRepo = $entityManager->getRepository(User::class);
            $blockedUsers = $userRepo->findBy(['id' => $blockedIds]);
            $map = [];
            foreach ($blockedUsers as $u) $map[$u->getId()] = $u;
            $ordered = [];
            foreach ($blockedIds as $id) if (isset($map[$id])) $ordered[] = $map[$id];
            $blockedUsers = $ordered;
        }

        return $this->render('profile/settings.html.twig', [
            'blockedUsers' => $blockedUsers,
        ]);
    }

    /**
     * Ajoute ou supprime un utilisateur du réseau de l'utilisateur courant
     *
     * @param int $userId ID de l'utilisateur à ajouter/supprimer du réseau
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return JsonResponse La réponse JSON indiquant le résultat de l'opération
     */
    #[Route('/network/toggle/{userId}', name: 'network_toggle', methods: ['POST'])]
    public function toggleNetwork(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        $me = $this->getUser();
        if (!$me) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        if ($me->getId() === (int)$userId) {
            return $this->json(['error' => 'Cannot add yourself to network'], 400);
        }

        $userRepo = $entityManager->getRepository(User::class);
        $other = $userRepo->find($userId);
        if (!$other) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Respecter les blocages : refuser l'action si l'un bloque l'autre
        if ($me->isBlocked($other->getId()) || $other->isBlocked($me->getId())) {
            return $this->json(['error' => 'Blocked'], 403);
        }

        $notifRepo = $entityManager->getRepository(Notification::class);

        // 1) Si déjà en réseau -> suppression mutuelle
        if ($me->isInNetwork($other->getId())) {
            $me->removeFromNetwork($other->getId());
            $other->removeFromNetwork($me->getId());
            $entityManager->persist($me);
            $entityManager->persist($other);
            $entityManager->flush();

            return $this->json(['success' => true, 'removed' => true]);
        }

        // 2) Si demande sortante pendante existante -> annuler la demande
        $outgoing = $notifRepo->findOneBy([
            'sender' => $me,
            'recipient' => $other,
            'type' => 'network_request',
            'status' => 'pending'
        ]);
        if ($outgoing) {
            $entityManager->remove($outgoing);
            $entityManager->flush();
            return $this->json(['success' => true, 'cancelled' => true]);
        }

        // 3) Si il y a une demande entrante (other -> me) -> accepter la demande
        $incoming = $notifRepo->findOneBy([
            'sender' => $other,
            'recipient' => $me,
            'type' => 'network_request',
            'status' => 'pending'
        ]);
        if ($incoming) {
            // créer la connexion mutuelle
            $me->addToNetwork($other->getId());
            $other->addToNetwork($me->getId());

            // supprimer la notification entrante (accepte)
            $entityManager->remove($incoming);

            // créer conversation si nécessaire
            $convRepo = $entityManager->getRepository(Conversation::class);
            $qb = $convRepo->createQueryBuilder('c');
            $qb->where('(c.user1 = :a AND c.user2 = :b) OR (c.user1 = :b AND c.user2 = :a)')
                ->setParameter('a', $me)
                ->setParameter('b', $other)
                ->setMaxResults(1);
            $conv = $qb->getQuery()->getOneOrNullResult();

            if (!$conv) {
                $conv = new Conversation();
                if (method_exists($conv, 'setUser1') && method_exists($conv, 'setUser2')) {
                    $conv->setUser1($me);
                    $conv->setUser2($other);
                } elseif (method_exists($conv, 'setUser')) {
                    // fallback minimal — adapte si nécessaire
                    $conv->setUser($me);
                }
                $entityManager->persist($conv);
            }

            $entityManager->persist($me);
            $entityManager->persist($other);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'accepted' => true,
                'conversationId' => $conv->getId() ?? null
            ]);
        }

        // 4) Sinon : créer une notification de demande (pending) — l'autre devra accepter via notifications
        $notification = new Notification();
        $notification->setType('network_request');
        $notification->setSender($me);
        $notification->setRecipient($other);
        $notification->setStatus('pending');
        $notification->setData(['message' => sprintf('%s souhaite rejoindre votre réseau', $me->getUsername() ?? 'Quelqu\'un')]);

        $entityManager->persist($notification);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'pending' => true
        ]);
    }

    /**
     * Liste les notifications de l'utilisateur courant
     *
     * @param Request $request La requête HTTP
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return JsonResponse La réponse JSON contenant les notifications
     */
    #[Route('/notifications', name: 'notifications_list', methods: ['GET'])]
    public function listNotifications(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $me = $this->getUser();
        if (!$me) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $notifRepo = $entityManager->getRepository(Notification::class);
        $notifications = $notifRepo->findBy(['recipient' => $me], ['createdAt' => 'DESC']);

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
                'sender' => $sender ? ['id' => $sender->getId(), 'username' => $sender->getUsername()] : null,
                'createdAt' => $n->getCreatedAt()->format(DateTime::ATOM),
            ];
            if (!$n->isRead()) $unread++;
        }

        return $this->json(['notifications' => $out, 'count' => count($out), 'unread' => $unread]);
    }

    /**
     * Accepte une notification (ex: demande de connexion)
     *
     * @param int $id ID de la notification
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return JsonResponse La réponse JSON
     */
    #[Route('/notifications/accept/{id}', name: 'notifications_accept', methods: ['POST'])]
    public function acceptNotification(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $me = $this->getUser();
        if (!$me) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $notifRepo = $entityManager->getRepository(Notification::class);
        $notification = $notifRepo->find($id);
        if (!$notification) {
            return $this->json(['error' => 'Notification not found'], 404);
        }
        if ($notification->getRecipient()->getId() !== $me->getId()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if ($notification->getType() !== 'network_request' || $notification->getStatus() !== 'pending') {
            return $this->json(['error' => 'Invalid notification'], 400);
        }

        $sender = $notification->getSender();
        if (!$sender) {
            // sender missing, just remove notification
            $entityManager->remove($notification);
            $entityManager->flush();
            return $this->json(['success' => true]);
        }

        // créer connexion mutuelle
        if (!$me->isInNetwork($sender->getId())) $me->addToNetwork($sender->getId());
        if (!$sender->isInNetwork($me->getId())) $sender->addToNetwork($me->getId());

        // supprimer la notification (ou marquer acceptée)
        $entityManager->remove($notification);

        // créer conversation si nécessaire
        $convRepo = $entityManager->getRepository(Conversation::class);
        $qb = $convRepo->createQueryBuilder('c');
        $qb->where('(c.user1 = :a AND c.user2 = :b) OR (c.user1 = :b AND c.user2 = :a)')
            ->setParameter('a', $me)
            ->setParameter('b', $sender)
            ->setMaxResults(1);
        $conv = $qb->getQuery()->getOneOrNullResult();

        if (!$conv) {
            $conv = new Conversation();
            if (method_exists($conv, 'setUser1') && method_exists($conv, 'setUser2')) {
                $conv->setUser1($me);
                $conv->setUser2($sender);
            } elseif (method_exists($conv, 'setUser')) {
                $conv->setUser($me); // fallback pour entités différentes
            }
            $entityManager->persist($conv);
        }

        $entityManager->persist($me);
        $entityManager->persist($sender);
        $entityManager->flush();

        return $this->json(['success' => true, 'accepted' => true, 'conversationId' => $conv->getId() ?? null]);
    }

    /**
     * Décline une notification (ex: demande de connexion)
     *
     * @param int $id ID de la notification
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return JsonResponse La réponse JSON
     */
    #[Route('/notifications/decline/{id}', name: 'notifications_decline', methods: ['POST'])]
    public function declineNotification(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $me = $this->getUser();
        if (!$me) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $notifRepo = $entityManager->getRepository(Notification::class);
        $notification = $notifRepo->find($id);
        if (!$notification) {
            return $this->json(['error' => 'Notification not found'], 404);
        }
        if ($notification->getRecipient()->getId() !== $me->getId()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        // Pour une déclinaison simple, on supprime la notification
        $entityManager->remove($notification);
        $entityManager->flush();

        return $this->json(['success' => true, 'declined' => true]);
    }

    /**
     * Efface toutes les notifications de l'utilisateur courant
     *
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités
     * @return JsonResponse La réponse JSON
     */
    #[Route('/notifications/clear-all', name: 'notifications_clear_all', methods: ['POST'])]
    public function clearAllNotifications(EntityManagerInterface $entityManager): JsonResponse
    {
        $me = $this->getUser();
        if (!$me) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $notifRepo = $entityManager->getRepository(Notification::class);
        $notifications = $notifRepo->findBy(['recipient' => $me]);

        foreach ($notifications as $n) {
            $entityManager->remove($n);
        }
        $entityManager->flush();

        return $this->json(['success' => true, 'cleared' => true]);
    }
}
