<?php

/**
 * Contrôleur du pied de page
 *
 * Ce contrôleur gère l'affichage du pied de page (footer) commun à toutes les pages
 * Retourne un fragment HTML réutilisable
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FooterController extends AbstractController
{
    /**
     * Génère le rendu du pied de page
     *
     * @return Response Fragment HTML du footer
     */
    #[Route('/footer', name: 'app_footer')]
    public function footer(): Response
    {
        return $this->render('components/_footer.html.twig', [
            'controller_name' => 'FooterController',
        ]);
    }
}
