<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use App\Service\NetworkService;

final class MapsController extends AbstractController
{
    #[Route('/maps', name: 'app_maps')]
    public function index(UserRepository $userRepository, NetworkService $networkService): Response
    {
        $currentUser = $this->getUser();
        $users = $userRepository->findAll();
        
        // Récupérer les IDs des amis si l'utilisateur est connecté
        $friendIds = [];
        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
        }

        return $this->render('maps/index.html.twig', [
            'controller_name' => 'MapsController',
            'users' => $users,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser ? $currentUser->getId() : null,
        ]);
    }

    #[Route('/maps/filter', name: 'app_user_map_filter')]
    public function filter(Request $request, UserRepository $userRepository, NetworkService $networkService): Response
    {
        $currentUser = $this->getUser();
        $tagIds = $request->query->all('tags');
        
        $users = $userRepository->findByTaggableQuestion1Tags($tagIds);
        
        // Récupérer les IDs des amis si l'utilisateur est connecté
        $friendIds = [];
        if ($currentUser) {
            $friends = $networkService->getUserNetwork($currentUser);
            $friendIds = array_map(fn($friend) => $friend->getId(), $friends);
        }

        return $this->render('maps/_bubbles.html.twig', [
            'users' => $users,
            'friend_ids' => $friendIds,
            'current_user_id' => $currentUser ? $currentUser->getId() : null,
        ]);
    }
}