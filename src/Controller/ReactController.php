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
        $env = $this->getParameter('kernel.environment');
        
        // En production, charger le manifest Vite
        if ($env === 'prod') {
            $manifestPath = $this->getParameter('kernel.project_dir') . '/public/react/.vite/manifest.json';
            
            if (!file_exists($manifestPath)) {
                error_log('REACT DEPLOYMENT ERROR: Manifest not found at ' . $manifestPath);
                error_log('Please run: cd frontend && npm run build');
            } else {
                $manifestContent = file_get_contents($manifestPath);
                $manifest = json_decode($manifestContent, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('REACT DEPLOYMENT ERROR: Invalid JSON in manifest: ' . json_last_error_msg());
                }
            }
        }
        
        return $this->render('react/index.html.twig', [
            'manifest' => $manifest,
            'environment' => $env,
        ]);
    }
}
