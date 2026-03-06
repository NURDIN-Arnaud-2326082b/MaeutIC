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
        return $this->render('react/index.html.twig');
    }
}
