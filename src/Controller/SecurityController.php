<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, HttpClientInterface $httpClient): Response
    {
        $recaptchaResponse = $request->request->get('g-recaptcha-response');

        if ($recaptchaResponse) {
            try {
                $response = $httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                    'body' => [
                        'secret' => $_ENV['6Lfx-gIsAAAAADs2WiCDrn0kn74HyGrdVtLb637C'],
                        'response' => $recaptchaResponse,
                        'remoteip' => $request->getClientIp(),
                    ],
                ]);

                $data = $response->toArray();

                // Si la vérification échoue ou le score est faible
                if (!$data['success'] || ($data['score'] ?? 0) < 0.5) {
                    $this->addFlash('error', 'La vérification reCAPTCHA a échoué. Veuillez réessayer.');
                    return $this->redirectToRoute('app_login');
                }
            } catch (\Exception $e) {
                // En cas de problème avec l’API
                $this->addFlash('error', 'Erreur lors de la vérification reCAPTCHA. Veuillez réessayer.');
                return $this->redirectToRoute('app_login');
            }
        }

        // Gestion classique du login Symfony
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut rester vide — interceptée par le firewall.');
    }
}