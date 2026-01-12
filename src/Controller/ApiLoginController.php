<?php

/**
 * Contrôleur API de connexion
 *
 * Ce contrôleur gère les endpoints API pour l'authentification
 * Actuellement un placeholder pour futures fonctionnalités API
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ApiLoginController extends AbstractController
{
    /**
     * Endpoint API de connexion (placeholder)
     *
     * @return JsonResponse Message de bienvenue
     */
    #[Route('/api/login', name: 'app_api_login')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ApiLoginController.php',
        ]);
    }
}
