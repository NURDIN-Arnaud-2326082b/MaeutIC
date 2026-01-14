<?php

/**
 * Contrôleur de réinitialisation de mot de passe
 *
 * Ce contrôleur gère le processus complet de réinitialisation de mot de passe :
 * - Demande de réinitialisation par email
 * - Génération et envoi de token sécurisé
 * - Vérification du token et changement du mot de passe
 * - Protection contre les abus
 */

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Form\ForgotPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForgotPasswordController extends AbstractController
{
    /**
     * Gère la demande de réinitialisation de mot de passe
     *
     * @throws RandomException
     */
    #[Route('/forgot-password', name: 'app_forgot_password_request')]
    public function request(
        Request                      $request,
        UserRepository               $userRepository,
        PasswordResetTokenRepository $tokenRepository,
        EntityManagerInterface       $entityManager,
        MailerInterface              $mailer
    ): Response
    {
        $form = $this->createForm(ForgotPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];

            $user = $userRepository->findOneBy(['email' => $email]);

            // Ne pas révéler si l'email existe ou non
            if ($user) {
                // Vérifier qu'il n'y a pas trop de demandes récentes pour cet utilisateur
                $since = new DateTimeImmutable('-1 hour');
                $recentRequests = $tokenRepository->countRecentRequests($user, $since);

                if ($recentRequests < 3) {
                    // Supprimer les anciens tokens de cet utilisateur
                    $tokenRepository->removeTokensForUser($user);

                    // Créer un nouveau token sécurisé
                    $token = bin2hex(random_bytes(32));
                    $hashedToken = password_hash($token, PASSWORD_DEFAULT);

                    $resetToken = new PasswordResetToken();
                    $resetToken->setUser($user);
                    $resetToken->setToken($hashedToken);
                    $resetToken->setExpiresAt(new DateTimeImmutable('+1 hour'));

                    $entityManager->persist($resetToken);
                    $entityManager->flush();

                    try {
                        // Envoyer l'email
                        $emailSent = $this->sendResetEmail($mailer, $user, $token);

                        if ($emailSent) {
                            // Email envoyé avec succès
                            return $this->redirectToRoute('app_forgot_password_email_sent');
                        } else {
                            // Échec d'envoi d'email
                            $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer plus tard.');
                        }

                    } catch (TransportExceptionInterface $e) {
                        // Erreur d'envoi d'email
                        error_log('Email sending failed for ' . $user->getEmail() . ': ' . $e->getMessage());
                        $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer plus tard.');
                    }
                } else {
                    // Trop de demandes récentes
                    $this->addFlash('error', 'Trop de demandes de réinitialisation. Veuillez réessayer dans 1 heure.');
                }
            } else {
                // Email n'existe pas, on redirige quand même vers la page de confirmation
                return $this->redirectToRoute('app_forgot_password_email_sent');
            }
        }

        return $this->render('forgot_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Envoie l'email de réinitialisation de mot de passe
     *
     * Génère le lien de réinitialisation et envoie l'email à l'utilisateur
     * En mode debug, log l'email au lieu de l'envoyer
     *
     * @param MailerInterface $mailer Service d'envoi d'emails
     * @param User $user L'utilisateur demandant la réinitialisation
     * @param string $token Le token de réinitialisation
     * @return bool True si l'email a été envoyé avec succès
     */
    private function sendResetEmail(MailerInterface $mailer, User $user, string $token): bool
    {
        try {
            $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new Email())
                ->from('contact@maieutic-projet.fr')
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe - Maieutic')
                ->html($this->renderView('email/reset_password.html.twig', [
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                    'expiration_date' => new DateTimeImmutable('+1 hour'),
                ]));

            // DEBUG: Affiche l'email dans les logs
            $emailText = sprintf(
                "EMAIL WOULD BE SENT:\nTo: %s\nSubject: %s\nURL: %s\nBody: %s",
                $user->getEmail(),
                $email->getSubject(),
                $resetUrl,
                $email->getHtmlBody()
            );

            error_log($emailText);

            if (php_sapi_name() === 'cli') {
                echo "\nEMAIL WOULD BE SENT:\n";
                echo "To: " . $user->getEmail() . "\n";
                echo "Subject: " . $email->getSubject() . "\n";
                echo "URL: " . $resetUrl . "\n";
                echo "Body content preview...\n\n";
            }

            if ($_ENV['MAILER_DSN'] !== 'null://null') {
                $mailer->send($email);
                return true;
            }

            // En mode null://null, on considère que c'est un succès pour les tests
            return true;

        } catch (TransportExceptionInterface $e) {
            // Log l'erreur
            error_log('Failed to send reset email to ' . $user->getEmail() . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Affiche la page de confirmation d'envoi d'email
     *
     * @return Response Affiche la page de confirmation d'envoi d'email
     */
    #[Route('/forgot-password/email-sent', name: 'app_forgot_password_email_sent')]
    public function emailSent(): Response
    {
        return $this->render('forgot_password/email_sent.html.twig');
    }

    /**
     * Gère la réinitialisation du mot de passe via le token
     *
     * @param Request $request La requête HTTP
     * @param string $token Le token de réinitialisation
     * @param PasswordResetTokenRepository $tokenRepository Le dépôt de tokens de réinitialisation
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     * @param UserPasswordHasherInterface $passwordHasher Le service de hachage de mot de passe
     * @return Response La réponse HTTP
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(
        Request                      $request,
        string                       $token,
        PasswordResetTokenRepository $tokenRepository,
        EntityManagerInterface       $entityManager,
        UserPasswordHasherInterface  $passwordHasher
    ): Response
    {
        // Nettoyer les tokens expirés
        $tokenRepository->cleanupExpiredTokens();

        // Chercher un token valide en vérifiant le hash
        $validToken = null;
        $allTokens = $tokenRepository->findBy(['used' => false]);

        foreach ($allTokens as $resetToken) {
            if (password_verify($token, $resetToken->getToken()) && $resetToken->isValid()) {
                $validToken = $resetToken;
                break;
            }
        }

        if (!$validToken) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $user = $validToken->getUser();
        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Hash et sauvegarde du nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $data['plainPassword']
            );

            $user->setPassword($hashedPassword);

            // Marquer le token comme utilisé
            $validToken->setUsed(true);

            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('forgot_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}