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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractController
{
    /**
     * Affiche le formulaire de connexion et gère l'authentification
     *
     * @param Request $request La requête HTTP
     * @param AuthenticationUtils $authenticationUtils Utilitaire pour récupérer les erreurs d'authentification
     * @param HttpClientInterface $httpClient Client HTTP pour les appels externes
     * @return Response La page de connexion rendue
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, HttpClientInterface $httpClient): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,


        ]);
    }

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
