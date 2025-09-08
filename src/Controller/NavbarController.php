<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NavbarController extends AbstractController{
    #[Route('/navbar', name: 'app_navbar')]
    public function navbar(): Response
    {
        return $this->render('components/_navbar.html.twig', [
            'controller_name' => 'NavbarController',
        ]);
    }
}
