<?php

namespace App\Controller\Api;

use App\Entity\Message;
use App\Repository\MessageRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chat')]
final class ChatApiController extends AbstractController
{
    /**
     * Récupère tous les messages du chat global
     *
     * @param MessageRepository $messageRepository Repository des messages
     * @return JsonResponse Liste des messages avec informations des expéditeurs
     */
    #[Route('/messages', name: 'api_chat_messages', methods: ['GET'])]
    public function getMessages(MessageRepository $messageRepository): JsonResponse
    {
        $messages = $messageRepository->findBy(['conversation' => null], ['sentAt' => 'ASC']);

        $data = array_map(function (Message $msg) {
            $sender = $msg->getSender();

            return [
                'sender' => $sender ? [
                    'username' => $sender->getUsername(),
                    'profileImage' => $sender->getProfileImage(),
                ] : null,
                'sentAt' => $msg->getSentAt() ? $msg->getSentAt()->format('d/m/Y H:i') : '',
                'content' => $msg->getContent(),
            ];
        }, $messages);

        return new JsonResponse($data);
    }

    /**
     * Envoie un message dans le chat global
     *
     * @param Request $request La requête contenant le texte du message
     * @param EntityManagerInterface $em Gestionnaire d'entités
     * @return JsonResponse Statut de l'envoi ou erreur
     */
    #[Route('/send', name: 'api_chat_send', methods: ['POST'])]
    public function sendMessage(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['text']) || empty(trim($data['text']))) {
            return new JsonResponse(['error' => 'Message text is required'], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $message = new Message();
            $message->setContent($data['text']);
            $message->setSender($user);
            $message->setSentAt(new DateTime());
            $message->setConversation(null); // Chat général
            $em->persist($message);
            $em->flush();

            return new JsonResponse(['status' => 'Message sent']);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
