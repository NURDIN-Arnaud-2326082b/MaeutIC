<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReactController extends AbstractController
{
    /**
     * Catch-all route for React SPA
     * This should be the last route in your routing config
     */
    #[Route('/{reactRouting}', name: 'react_app', requirements: ['reactRouting' => '.*'], priority: -1)]
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
