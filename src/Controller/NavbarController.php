<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\NotificationRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

final class NavbarController extends AbstractController
{
    #[Route('/navbar', name: 'app_navbar')]
    public function navbar(NotificationRepository $notificationRepo, UrlGeneratorInterface $urlGenerator): Response
    {
        $user = $this->getUser();
        $notificationsCount = 0;
        if ($user) {
            $pending = $notificationRepo->findPendingByRecipient($user);
            $notificationsCount = is_array($pending) ? count($pending) : 0;
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
