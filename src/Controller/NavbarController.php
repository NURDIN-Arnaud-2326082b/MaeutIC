<?php

/**
 * Contrôleur de la barre de navigation
 *
 * Ce contrôleur gère l'affichage de la barre de navigation avec :
 * - Compteur de notifications non lues
 * - Génération des URLs de navigation
 * - Affichage contextuel selon l'état de connexion
 */

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NavbarController extends AbstractController
{
    /**
     * Génère le rendu de la barre de navigation
     *
     * @param NotificationRepository $notificationRepo Repository des notifications
     * @param UrlGeneratorInterface $urlGenerator Générateur d'URLs
     * @return Response Fragment HTML de la navbar
     */
    #[Route('/navbar', name: 'app_navbar')]
    public function navbar(NotificationRepository $notificationRepo, UrlGeneratorInterface $urlGenerator): Response
    {
        $user = $this->getUser();
        $notificationsCount = 0;
        if ($user) {
            $pending = $notificationRepo->findPendingByRecipient($user);
            $notificationsCount = count($pending);
        }

        // Tenter de générer la route nommée 'notifications_list' si elle existe,
        // sinon utiliser l'URL littérale de fallback '/notifications/list'.
        try {
            $notificationsUrl = $urlGenerator->generate('notifications_list');
        } catch (RouteNotFoundException $e) {
            $notificationsUrl = '/notifications/list';
        }

        return $this->render('components/_navbar.html.twig', [
            'controller_name' => 'NavbarController',
            'notificationsCount' => $notificationsCount,
            'notificationsUrl' => $notificationsUrl,
        ]);
    }
}
