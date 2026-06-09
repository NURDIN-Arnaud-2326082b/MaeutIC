<?php

namespace App\Controller\Api;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Pusher\Pusher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/conversation')]
final class ConversationApiController extends AbstractController
{
    private const BANNED_DISPLAY_NAME = 'utilisateur banni';
    private const DELETED_DISPLAY_NAME = 'utilisateur supprimé';
    private const DEFAULT_PROFILE_IMAGE = '/images/default-profile.png';

    private function serializeConversationUser(?User $user): array
    {
        if (!$user) {
            return [
                'id' => null,
                'username' => self::DELETED_DISPLAY_NAME,
                'profileImage' => self::DEFAULT_PROFILE_IMAGE,
            ];
        }

        if ($user->isBanned()) {
            return [
                'id' => $user->getId(),
                'username' => self::BANNED_DISPLAY_NAME,
                'profileImage' => self::DEFAULT_PROFILE_IMAGE,
            ];
        }

        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'profileImage' => $user->getProfileImage() ? '/profile_images/' . $user->getProfileImage() : null,
        ];
    }

    /**
     * Liste toutes les conversations de l'utilisateur connecté
     *
     * @param ConversationRepository $conversationRepo Repository des conversations
     * @return JsonResponse Liste des conversations avec infos utilisateur et dernier message
     */
    #[Route('s', name: 'api_conversations_list', methods: ['GET'])]
    public function listConversations(ConversationRepository $conversationRepo): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $conversations = $conversationRepo->createQueryBuilder('c')
            ->where('c.user1 = :user OR c.user2 = :user')
            ->setParameter('user', $user)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(function (Conversation $conversation) use ($user) {
            $userId = $user->getId();
            $user1 = $conversation->getUser1();
            $user2 = $conversation->getUser2();

            $other = null;
            if ($user1 && $user1->getId() === $userId) {
                $other = $user2;
            } elseif ($user2 && $user2->getId() === $userId) {
                $other = $user1;
            }

            // Si l'autre participant a été supprimé, on garde la conversation visible
            // et on laisse serializeConversationUser(null) produire un placeholder.
            $isBlocked = false;
            if ($other) {
                // Vérifier les blocages
                $isBlocked = $user->isBlocked($other->getId()) || $other->isBlocked($user->getId());
            }

            // Récupérer le dernier message
            $messages = $conversation->getMessages()->toArray();
            usort($messages, fn($a, $b) => $b->getSentAt() <=> $a->getSentAt());
            $lastMessage = !empty($messages) ? $messages[0] : null;

            return [
                'id' => $conversation->getId(),
                'otherUser' => $this->serializeConversationUser($other),
                'lastMessage' => $lastMessage ? [
                    'content' => $lastMessage->getContent(),
                    'sentAt' => $lastMessage->getSentAt()->format('d/m/Y H:i'),
                ] : null,
                'isBlocked' => $isBlocked,
            ];
        }, $conversations);

        return new JsonResponse($data);
    }

    /**
     * Récupère les messages d'une conversation spécifique
     *
     * @param Conversation $conversation La conversation
     * @param MessageRepository $messageRepo Repository des messages
     * @return JsonResponse Liste des messages ou erreur si blocage/accès refusé
     */
    #[Route('/{id}/messages', name: 'api_conversation_messages', methods: ['GET'])]
    public function getMessages(Conversation $conversation, MessageRepository $messageRepo): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier que l'utilisateur fait partie de la conversation
        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $other = null;
        if ($conversation->getUser1() && $conversation->getUser1()->getId() === $user->getId()) {
            $other = $conversation->getUser2();
        } elseif ($conversation->getUser2() && $conversation->getUser2()->getId() === $user->getId()) {
            $other = $conversation->getUser1();
        }

        // Si l'autre participant a été supprimé, on autorise l'accès à l'historique
        // et serializeConversationUser(null) renverra un placeholder.
        if ($other && ($user->isBlocked($other->getId()) || $other->isBlocked($user->getId()))) {
            return new JsonResponse(['error' => 'Conversation blocked'], Response::HTTP_FORBIDDEN);
        }

        $messages = $messageRepo->findBy(['conversation' => $conversation], ['sentAt' => 'ASC']);

        $data = [
            'conversationId' => $conversation->getId(),
            'otherUser' => $this->serializeConversationUser($other),
            'messages' => array_map(function (Message $message) use ($user) {
                $sender = $message->getSender();
                $senderId = $sender?->getId();
                $isOwn = false;

                if ($sender === null) {
                    $senderName = self::DELETED_DISPLAY_NAME;
                } else {
                    $senderName = $sender->getUsername();
                    if ($sender->isBanned()) {
                        $senderName = self::BANNED_DISPLAY_NAME;
                    }
                    $isOwn = $sender === $user;
                }

                return [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'sender' => [
                        'id' => $senderId,
                        'username' => $senderName,
                    ],
                    'sentAt' => $message->getSentAt()->format('d/m/Y H:i'),
                    'isOwn' => $isOwn,
                ];
            }, $messages),
        ];

        return new JsonResponse($data);
    }

    /**
     * Envoie un nouveau message dans une conversation
     *
     * @param Request $request La requête contenant le contenu du message
     * @param Conversation $conversation La conversation
     * @param EntityManagerInterface $em Gestionnaire d'entités
     * @return JsonResponse Message créé ou erreur
     */
    #[Route('/{id}/message', name: 'api_conversation_send_message', methods: ['POST'])]
    public function sendMessage(Request $request, Conversation $conversation, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier que l'utilisateur fait partie de la conversation
        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $other = ($conversation->getUser1() === $user) ? $conversation->getUser2() : $conversation->getUser1();
        
        // Vérifier les blocages
        if ($user->isBlocked($other->getId()) || $other->isBlocked($user->getId())) {
            return new JsonResponse(['error' => 'Cannot send message due to blocking'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $content = trim($data['content'] ?? '');

        if (empty($content)) {
            return new JsonResponse(['error' => 'Message content is required'], Response::HTTP_BAD_REQUEST);
        }

        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($user);
        $message->setContent($content);
        $message->setSentAt(new DateTime());

        $em->persist($message);
        $em->flush();

        $notification = new Notification();
        $notification->setType('private_message');
        $notification->setSender($user);
        $notification->setRecipient($other);
        $notification->setStatus('unread');
        $notification->setData([
            'conversationId' => $conversation->getId(),
            'messageId' => $message->getId(),
            'message' => mb_strlen($content) > 150 ? mb_substr($content, 0, 150) . '...' : $content,
        ]);

        $em->persist($notification);
        $em->flush();

        $pusherMessageData = [
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'sender' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ],
            'sentAt' => $message->getSentAt()->format('d/m/Y H:i'),
        ];

        $notificationData = [
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'data' => $notification->getData(),
            'status' => $notification->getStatus(),
            'isRead' => $notification->isRead(),
            'sender' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'profileImage' => $user->getProfileImage() ? '/profile_images/' . $user->getProfileImage() : null,
            ],
            'createdAt' => $notification->getCreatedAt()->format(
                \DateTime::ATOM
            ),
        ];

        try {
            $pusher = new Pusher(
                $_ENV['PUSHER_KEY'],
                $_ENV['PUSHER_SECRET'],
                $_ENV['PUSHER_APP_ID'],
                [
                    'cluster' => $_ENV['PUSHER_CLUSTER'],
                    'useTLS' => true
                ]
            );

            $pusher->trigger('private-conversation-' . $conversation->getId(), 'new-message', $pusherMessageData);
            $pusher->trigger('private-user-' . $other->getId(), 'new-notification', $notificationData);
        } catch (\Throwable $e) {
            error_log(sprintf(
                'Failed to trigger Pusher new-message event for conversation %d, message %d: %s',
                $conversation->getId(),
                $message->getId(),
                $e->getMessage()
            ));
        }

        return new JsonResponse([
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'sender' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ],
            'sentAt' => $message->getSentAt()->format('d/m/Y H:i'),
            'isOwn' => true,
        ], Response::HTTP_CREATED);
    }

    /**
     * Trouve ou crée une conversation avec un utilisateur spécifique
     *
     * @param int $userId ID de l'utilisateur avec qui converser
     * @param UserRepository $userRepo Repository des utilisateurs
     * @param ConversationRepository $conversationRepo Repository des conversations
     * @param EntityManagerInterface $em Gestionnaire d'entités
     * @return JsonResponse ID de la conversation ou erreur
     */
    #[Route('/with/{userId}', name: 'api_conversation_find_or_create', methods: ['GET'])]
    public function findOrCreateConversation(int $userId, UserRepository $userRepo, ConversationRepository $conversationRepo, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $other = $userRepo->find($userId);
        if (!$other || $other === $user) {
            return new JsonResponse(['error' => 'User not found or cannot message yourself'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier les blocages
        if ($user->isBlocked($other->getId()) || $other->isBlocked($user->getId())) {
            return new JsonResponse(['error' => 'Cannot start conversation due to blocking'], Response::HTTP_FORBIDDEN);
        }

        // Chercher une conversation existante
        $conversation = $conversationRepo->findOneBy(['user1' => $user, 'user2' => $other]);
        if (!$conversation) {
            $conversation = $conversationRepo->findOneBy(['user1' => $other, 'user2' => $user]);
        }

        // Créer une nouvelle conversation si elle n'existe pas
        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setUser1($user);
            $conversation->setUser2($other);
            $em->persist($conversation);
            $em->flush();
        }

        return new JsonResponse([
            'conversationId' => $conversation->getId(),
            'otherUser' => $this->serializeConversationUser($other),
        ]);
    }
}
