<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    #[Route('/api/check-email', name: 'api_check_email')]
    public function checkEmail(Request $request, UserRepository $userRepository): JsonResponse
    {
        $email = $request->query->get('email');
        
        if (!$email) {
            return new JsonResponse(['available' => true]);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        
        return new JsonResponse([
            'available' => $user === null,
            'message' => $user ? 'Cette adresse email est déjà utilisée' : 'Adresse email disponible'
        ]);
    }

    #[Route('/api/check-username', name: 'api_check_username')]
    public function checkUsername(Request $request, UserRepository $userRepository): JsonResponse
    {
        $username = $request->query->get('username');
        
        if (!$username) {
            return new JsonResponse(['available' => true]);
        }

        $user = $userRepository->findOneBy(['username' => $username]);
        
        return new JsonResponse([
            'available' => $user === null,
            'message' => $user ? 'Ce nom d\'utilisateur est déjà pris' : 'Nom d\'utilisateur disponible'
        ]);
    }
}