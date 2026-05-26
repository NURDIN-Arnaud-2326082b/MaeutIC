<?php

namespace App\Controller\Api;

use App\Repository\ConversationRepository;
use Pusher\Pusher;
use Pusher\PusherException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PusherAuthController extends AbstractController
{
    /**
     * @throws PusherException
     */
    #[Route('/api/pusher/auth', name: 'api_pusher_auth', methods: ['POST'])]
    public function authenticate(Request $request, ConversationRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new Response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $channelName = $request->request->get('channel_name');
        $socketId = $request->request->get('socket_id');

        if (preg_match('/^private-conversation-(\d+)$/', $channelName, $matches)) {
            $conversationId = $matches[1];
            $conversation = $repo->find($conversationId);

            if (!$conversation || ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user)) {
                return new Response('Forbidden', Response::HTTP_FORBIDDEN);
            }

            $pusher = new Pusher(
                $_ENV['PUSHER_KEY'],
                $_ENV['PUSHER_SECRET'],
                $_ENV['PUSHER_APP_ID'],
                [
                    'cluster' => $_ENV['PUSHER_CLUSTER'],
                    'useTLS' => true
                ]
            );

            return new Response($pusher->authorizeChannel($channelName, $socketId), Response::HTTP_OK, ['Content-Type' => 'application/json']);
        }

        if (preg_match('/^private-user-(\d+)$/', $channelName, $matches)) {
            $targetUserId = (int) $matches[1];

            if ($user->getId() !== $targetUserId) {
                return new Response('Forbidden', Response::HTTP_FORBIDDEN);
            }

            $pusher = new Pusher(
                $_ENV['PUSHER_KEY'],
                $_ENV['PUSHER_SECRET'],
                $_ENV['PUSHER_APP_ID'],
                [
                    'cluster' => $_ENV['PUSHER_CLUSTER'],
                    'useTLS' => true
                ]
            );

            return new Response($pusher->authorizeChannel($channelName, $socketId), Response::HTTP_OK, ['Content-Type' => 'application/json']);
        }

        return new Response('Invalid channel', Response::HTTP_BAD_REQUEST);
    }
}