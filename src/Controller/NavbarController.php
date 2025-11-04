<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\NotificationRepository; // <-- ajouté

final class NavbarController extends AbstractController{
    #[Route('/navbar', name: 'app_navbar')]
    public function navbar(NotificationRepository $notificationRepo): Response
    {
        $user = $this->getUser();
        $notificationsCount = 0;
        if ($user) {
            $pending = $notificationRepo->findPendingByRecipient($user);
            $notificationsCount = is_array($pending) ? count($pending) : 0;
        }

        return $this->render('components/_navbar.html.twig', [
            'controller_name' => 'NavbarController',
            'notificationsCount' => $notificationsCount, // <-- ajouté
        ]);
    }
}
