<?php

/**
 * Contrôleur de sécurité et authentification
 *
 * Gère toutes les opérations liées à l'authentification des utilisateurs :
 * connexion (login) et déconnexion (logout)
 * Utilise le système de sécurité Symfony pour authentifier les utilisateurs
 */

namespace App\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    /**
     * Gère la déconnexion de l'utilisateur
     *
     * La méthode est interceptée par le système de sécurité Symfony,
     * et n'est jamais exécutée directement
     *
     * @return void
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
