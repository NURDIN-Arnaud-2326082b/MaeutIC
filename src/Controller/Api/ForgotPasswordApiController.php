<?php

namespace App\Controller\Api;

use App\Entity\PasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/api/forgot-password')]
class ForgotPasswordApiController extends AbstractController
{
    /**
     * Request a password reset
     *
     * @throws RandomException
     */
    #[Route('/request', name: 'api_forgot_password_request', methods: ['POST'])]
    public function request(
        Request $request,
        UserRepository $userRepository,
        PasswordResetTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || empty(trim($data['email']))) {
            return $this->json(['error' => 'L\'email est requis'], Response::HTTP_BAD_REQUEST);
        }

        $email = trim($data['email']);
        $user = $userRepository->findOneBy(['email' => $email]);

        // Ne pas révéler si l'email existe ou non (sécurité)
        if ($user) {
            // Vérifier qu'il n'y a pas trop de demandes récentes
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
                    $this->sendResetEmail($mailer, $user, $token);
                } catch (TransportExceptionInterface $e) {
                    error_log('Email sending failed for ' . $user->getEmail() . ': ' . $e->getMessage());
                    return $this->json(
                        ['error' => 'Une erreur est survenue lors de l\'envoi de l\'email'],
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }
            } else {
                return $this->json(
                    ['error' => 'Trop de demandes de réinitialisation. Veuillez réessayer dans 1 heure.'],
                    Response::HTTP_TOO_MANY_REQUESTS
                );
            }
        }

        // Toujours retourner success pour ne pas révéler si l'email existe
        return $this->json([
            'message' => 'Si cet email existe dans notre base de données, un lien de réinitialisation a été envoyé.'
        ]);
    }

    /**
     * Verify if a reset token is valid
     */
    #[Route('/verify/{token}', name: 'api_forgot_password_verify', methods: ['GET'])]
    public function verify(
        string $token,
        PasswordResetTokenRepository $tokenRepository
    ): JsonResponse {
        // Nettoyer les tokens expirés
        $tokenRepository->cleanupExpiredTokens();

        // Chercher un token valide
        $validToken = null;
        $allTokens = $tokenRepository->findBy(['used' => false]);

        foreach ($allTokens as $resetToken) {
            if (password_verify($token, $resetToken->getToken()) && $resetToken->isValid()) {
                $validToken = $resetToken;
                break;
            }
        }

        if (!$validToken) {
            return $this->json(
                ['error' => 'Le lien de réinitialisation est invalide ou a expiré.'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json([
            'valid' => true,
            'expiresAt' => $validToken->getExpiresAt()->format('c')
        ]);
    }

    /**
     * Reset password with token
     */
    #[Route('/reset/{token}', name: 'api_forgot_password_reset', methods: ['POST'])]
    public function reset(
        string $token,
        Request $request,
        PasswordResetTokenRepository $tokenRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['password']) || empty(trim($data['password']))) {
            return $this->json(['error' => 'Le mot de passe est requis'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($data['password']) < 8) {
            return $this->json(
                ['error' => 'Le mot de passe doit contenir au moins 8 caractères'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Nettoyer les tokens expirés
        $tokenRepository->cleanupExpiredTokens();

        // Chercher un token valide
        $validToken = null;
        $allTokens = $tokenRepository->findBy(['used' => false]);

        foreach ($allTokens as $resetToken) {
            if (password_verify($token, $resetToken->getToken()) && $resetToken->isValid()) {
                $validToken = $resetToken;
                break;
            }
        }

        if (!$validToken) {
            return $this->json(
                ['error' => 'Le lien de réinitialisation est invalide ou a expiré.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $user = $validToken->getUser();

        // Hash et sauvegarde du nouveau mot de passe
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $data['password']
        );

        $user->setPassword($hashedPassword);

        // Marquer le token comme utilisé
        $validToken->setUsed(true);

        $entityManager->flush();

        return $this->json([
            'message' => 'Votre mot de passe a été réinitialisé avec succès.'
        ]);
    }

    /**
     * Send reset email
     */
    private function sendResetEmail(MailerInterface $mailer, $user, string $token): void
    {
        // Generate frontend URL (React app)
        $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000';
        $resetUrl = $frontendUrl . '/reset-password/' . $token;

        $email = (new Email())
            ->from('contact@maieutic-projet.fr')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe - Maieutic')
            ->html($this->renderView('email/reset_password.html.twig', [
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expiration_date' => new DateTimeImmutable('+1 hour'),
            ]));

        error_log(sprintf(
            "Password reset email sent to user ID %d at %s",
            $user->getId(),
            (new DateTimeImmutable())->format('c')
        ));

        if ($_ENV['MAILER_DSN'] !== 'null://null') {
            $mailer->send($email);
        }
    }
}
