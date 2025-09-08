<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;

final class MapsController extends AbstractController{
    #[Route('/maps', name: 'app_maps')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('maps/index.html.twig', [
            'controller_name' => 'MapsController',
            'users' => $users,
        ]);
    }

    #[Route('/maps/filter', name: 'app_user_map_filter')]
    public function filter(Request $request, UserRepository $userRepository): Response
    {
        $tagIds = $request->query->all('tags');
        // À adapter selon votre logique métier pour filtrer les users par tags de la première question taggable
        $users = $userRepository->findByTaggableQuestion1Tags($tagIds);
        return $this->render('maps/_bubbles.html.twig', [
            'users' => $users,
        ]);
    }
}
