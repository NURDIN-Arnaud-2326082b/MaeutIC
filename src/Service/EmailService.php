<?php

namespace App\Service;

use App\Entity\DataAccessRequest;
use App\Entity\User;
use DateTimeImmutable;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $mailerFrom,
        private string $frontendUrl,
        private Environment $twig
    ) {}

    /**
     * Send RGPD data export link email to user
     */
    public function sendDataExportLinkEmail(
        User $recipient,
        DataAccessRequest $request,
        string $rawToken,
        \DateTimeImmutable $expiresAt
    ): void
    {
        $baseUrl = rtrim($this->frontendUrl, '/');
        $downloadUrl = sprintf(
            '%s/privacy/data-access-download/%d?token=%s',
            $baseUrl,
            $request->getId(),
            urlencode($rawToken)
        );

        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($recipient->getEmail())
            ->subject('Vos données MaieutIC - Demande d\'accès RGPD')
            ->htmlTemplate('email/data_export.html.twig')
            ->context([
                'recipientName' => $recipient->getFirstName() ?: $recipient->getUsername(),
                'exportDate' => (new \DateTimeImmutable())->format('d/m/Y'),
                'expiresAt' => $expiresAt,
                'dataExportUrl' => $downloadUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a welcome email after account creation.
     */
    public function sendWelcomeEmail(User $recipient): void
    {
        $baseUrl = rtrim($this->frontendUrl, '/');
        $profileUrl = sprintf('%s/profile/%s', $baseUrl, urlencode($recipient->getUsername()));
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($recipient->getEmail())
            ->subject('Bienvenue sur MaieutIC')
            ->html($this->twig->render('email/welcome.html.twig', [
                'user' => $recipient,
                'baseUrl' => $baseUrl,
                'accountUrl' => $baseUrl,
                'profileUrl' => $profileUrl,
                'mapsUrl' => $baseUrl . '/maps',
                'libraryUrl' => $baseUrl . '/library',
                'generatedAt' => new DateTimeImmutable(),
            ]));

        if ($_ENV['MAILER_DSN'] !== 'null://null') {
            $this->mailer->send($email);
        }
    }
}
