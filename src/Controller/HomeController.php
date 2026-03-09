<?php

/**
 * Contrôleur de la page d'accueil
 *
 * Gère l'affichage de la page d'accueil
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    /**
     * Affiche la page d'accueil (SPA React)
     *
     * @return Response La page d'accueil React rendue
     */
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $manifest = null;
        
        // En production, charger le manifest Vite
        if ($this->getParameter('kernel.environment') === 'prod') {
            $manifestPath = $this->getParameter('kernel.project_dir') . '/public/react/.vite/manifest.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
            }
        }
        
        return $this->render('react/index.html.twig', [
            'manifest' => $manifest,
        ]);
    }
}
