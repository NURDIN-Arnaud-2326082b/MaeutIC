<?php

namespace App\Controller\Api;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/conversation')]
final class ConversationApiController extends AbstractController
{
    /**
     * Liste toutes les conversations de l'utilisateur connecté
     *
     * @param ConversationRepository $conversationRepo Repository des conversations
     * @return JsonResponse Liste des conversations avec infos utilisateur et dernier message
     */
    #[Route('s', name: 'api_conversations_list', methods: ['GET'])]
    public function listConversations(ConversationRepository $conversationRepo): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
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
            $other = ($conversation->getUser1() === $user) ? $conversation->getUser2() : $conversation->getUser1();
            
            // Vérifier les blocages
            $isBlocked = $user->isBlocked($other->getId()) || $other->isBlocked($user->getId());

            // Récupérer le dernier message
            $messages = $conversation->getMessages()->toArray();
            usort($messages, fn($a, $b) => $b->getSentAt() <=> $a->getSentAt());
            $lastMessage = !empty($messages) ? $messages[0] : null;

            return [
                'id' => $conversation->getId(),
                'otherUser' => [
                    'id' => $other->getId(),
                    'username' => $other->getUsername(),
                    'profileImage' => $other->getProfileImage() ? '/profile_images/' . $other->getProfileImage() : null,
                ],
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
        /** @var \App\Entity\User|null $user */
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
            return new JsonResponse(['error' => 'Conversation blocked'], Response::HTTP_FORBIDDEN);
        }

        $messages = $messageRepo->findBy(['conversation' => $conversation], ['sentAt' => 'ASC']);

        $data = [
            'conversationId' => $conversation->getId(),
            'otherUser' => [
                'id' => $other->getId(),
                'username' => $other->getUsername(),
                'profileImage' => $other->getProfileImage() ? '/profile_images/' . $other->getProfileImage() : null,
            ],
            'messages' => array_map(function (Message $message) use ($user) {
                return [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'sender' => [
                        'id' => $message->getSender()->getId(),
                        'username' => $message->getSender()->getUsername(),
                    ],
                    'sentAt' => $message->getSentAt()->format('d/m/Y H:i'),
                    'isOwn' => $message->getSender() === $user,
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
        /** @var \App\Entity\User|null $user */
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
        /** @var \App\Entity\User|null $user */
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
            'otherUser' => [
                'id' => $other->getId(),
                'username' => $other->getUsername(),
                'profileImage' => $other->getProfileImage() ? '/profile_images/' . $other->getProfileImage() : null,
            ],
        ]);
    }
}
