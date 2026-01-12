<?php

/**
 * Contrôleur de gestion des erreurs
 *
 * Ce contrôleur gère l'affichage des pages d'erreur personnalisées :
 * - Page 404 si ressource non trouvée
 * - Autres pages d'erreur si nécessaire
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends AbstractController
{
    /**
     * Affiche la page d'erreur 404 personnalisée
     *
     * @return Response Page 404 avec code HTTP approprié
     */
    #[Route('/error/404', name: 'app_error_404')]
    public function show404(): Response
    {
        return $this->render('bundles/TwigBundle/Exception/error404.html.twig', [], new Response('', 404));
    }
}