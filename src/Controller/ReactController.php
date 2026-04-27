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
    #[Route(
        '/{reactRouting}',
        name: 'react_app',
        requirements: [
            // Exclude API, static files, and direct file requests from SPA fallback.
            'reactRouting' => '(?!api(?:/|$))(?!react(?:/|$))(?!assets(?:/|$))(?!js(?:/|$))(?!images(?:/|$))(?!audio(?:/|$))(?!article_images(?:/|$))(?!article_pdfs(?:/|$))(?!author_images(?:/|$))(?!book_images(?:/|$))(?!post_images(?:/|$))(?!post_pdfs(?:/|$))(?!profile_images(?:/|$))(?!clusters\.json$)(?!manifest\.json$)(?!sw\.js$)(?!favicon\.ico$)(?!robots\.txt$)(?!.*\.[a-zA-Z0-9]+$).*',
        ],
        methods: ['GET'],
        priority: -1
    )]
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
        
        $response = $this->render('react/index.html.twig', [
            'manifest' => $manifest,
            'environment' => $env,
        ]);

        // Always serve the SPA shell without browser/proxy caching to avoid stale HTML after deploy.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
