<?php

namespace App\Controller\Api;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Pusher\Pusher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

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
                'id' => $msg->getId(),
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
     * @throws GuzzleException
     */
    #[Route('/send', name: 'api_chat_send', methods: ['POST'])]
    public function sendMessage(Request $request, EntityManagerInterface $em, LoggerInterface $logger): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['text']) || empty(trim($data['text']))) {
            return new JsonResponse(['error' => 'Message text is required'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User|null $user */
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

            $messageData = [
                'id' => $message->getId(),
                'sender' => [
                    'username' => $user->getUsername(),
                    'profileImage' => $user->getProfileImage(),
                ],
                'sentAt' => $message->getSentAt()->format('d/m/Y H:i'),
                'content' => $message->getContent(),
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

                $pusher->trigger('chat-global', 'new-message', $messageData);
            } catch (Exception $e) {
                $logger->error('Chat realtime publish error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'messageId' => $message->getId(),
                ]);
            }

            return new JsonResponse(['status' => 'Message sent']);
        } catch (Exception $e) {
            $logger->error('Chat send error: ' . $e->getMessage(), ['exception' => $e]);
            return new JsonResponse(['error' => 'An error occurred while sending the message. Please try again later.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
