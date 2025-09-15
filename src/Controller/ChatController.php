<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Conversation;
use App\Repository\MessageRepository;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController{
    
    // Public chat (maybe chats in the future)

    // Displays the global chat page with all conversations listed
    #[Route('/chat/global', name: 'app_chat_global')]
    public function showGlobalChat(ConversationRepository $conversationRepo): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $conversations = $conversationRepo->createQueryBuilder('c')
            ->where('c.user1 = :user OR c.user2 = :user')
            ->setParameter('user', $user)
            ->orderBy('c.id', 'DESC')
            ->getQuery()->getResult();

        return $this->render('chat/index.html.twig', [
            'controller_name' => 'ChatController',
            'conversations' => $conversations,
        ]);
    }

    #[Route('/chat/global/messages', name: 'chat_messages_global', methods: ['GET'])]
    public function getMessages(MessageRepository $messageRepository): JsonResponse
    {
        $messages = $messageRepository->findBy(['conversation' => null], ['sentAt' => 'ASC']);

        $data = array_map(function(Message $msg) {
            $sender = $msg->getSender();

            return [
                'sender' => $sender ? [
                    'username' => $sender->getUsername(),
                    'profileImage' => $sender->getProfileImage(),
                ] : null, // <- fallback si plus de sender
                'sentAt' => $msg->getSentAt() ? $msg->getSentAt()->format('d/m/Y H:i') : '',
                'content' => $msg->getContent(),
            ];
        }, $messages);

        return new JsonResponse($data);
    }

    #[Route('/chat/global/send', name: 'chat_send', methods: ['POST'])]
    public function sendMessage(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['text']) || empty(trim($data['text']))) {
            return new JsonResponse(['error' => 'Message text is required'], Response::HTTP_BAD_REQUEST);
        }
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        try {
            $message = new Message();
            $message->setContent($data['text']);
            $message->setSender($user);
            $message->setSentAt(new \DateTime());
            $message->setConversation(null); // Chat général
            $em->persist($message);
            $em->flush();
            return new JsonResponse(['status' => 'Message sent']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Private messages

    // Shows a specific conversation between two users
    #[Route('/chat/private/{id}', name: 'private_conversation')]
    public function showConversation(Conversation $conversation, ConversationRepository $conversationRepo, MessageRepository $messageRepo): Response
    {
        $user = $this->getUser();

        $conversations = $conversationRepo->createQueryBuilder('c')
            ->where('c.user1 = :user OR c.user2 = :user')
            ->setParameter('user', $user)
            ->orderBy('c.id', 'DESC')
            ->getQuery()->getResult();

        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            throw $this->createAccessDeniedException();
        }
        $messages = $messageRepo->findBy(['conversation' => $conversation], ['sentAt' => 'ASC']);
    
        return $this->render('chat/index.html.twig', [
            'controller_name' => 'ChatController',
            'conversations' => $conversations,
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    // Creates a new conversation with another user
    #[Route('/chat/private/new/{userId}', name: 'private_message_new')]
    public function newConversation(int $userId, UserRepository $userRepo, ConversationRepository $conversationRepo, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $other = $userRepo->find($userId);
        if (!$other || $other === $user) {
            throw $this->createNotFoundException();
        }
        $conversation = $conversationRepo->createQueryBuilder('c')
            ->where('(c.user1 = :user1 AND c.user2 = :user2) OR (c.user1 = :user2 AND c.user2 = :user1)')
            ->setParameter('user1', $user)
            ->setParameter('user2', $other)
            ->getQuery()->getOneOrNullResult();
        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setUser1($user);
            $conversation->setUser2($other);
            $em->persist($conversation);
            $em->flush();
        }
        return $this->redirectToRoute('private_conversation', ['id' => $conversation->getId()]);
    }

    // Sends a message in a specific conversation
    #[Route('/chat/private/{id}/send', name: 'private_message_send', methods: ['POST'])]
    public function sendPrivateMessage(Request $request, Conversation $conversation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            throw $this->createAccessDeniedException();
        }
        $content = trim($request->request->get('content'));
        if ($content) {
            $message = new Message();
            $message->setConversation($conversation);
            $message->setSender($user);
            $message->setContent($content);
            $message->setSentAt(new \DateTime());
            $em->persist($message);
            $em->flush();
        }
        return $this->redirectToRoute('private_conversation', ['id' => $conversation->getId()]);
    }

    // AJAX endpoint to fetch messages for a specific conversation
    #[Route('/chat/private/{id}/ajax', name: 'private_conversation_ajax')]
    public function ajaxMessages(Conversation $conversation, MessageRepository $messageRepo): Response
    {
        $user = $this->getUser();
        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            throw $this->createAccessDeniedException();
        }
        $messages = $messageRepo->findBy(['conversation' => $conversation], ['sentAt' => 'ASC']);
        return $this->render('components/_messages.html.twig', [
            'messages' => $messages,
        ]);
    }
}
