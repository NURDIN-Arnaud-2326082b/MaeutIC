<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;

class EmailService
{
    public function __construct(private MailerInterface $mailer) {}

    /**
     * Send RGPD data export email to user
     */
    public function sendDataExportEmail(User $recipient, array $exportData): void
    {
        $jsonData = json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        $email = (new TemplatedEmail())
            ->from('noreply@maeutic.local')
            ->to($recipient->getEmail())
            ->subject('Vos données MaeutIC - Demande d\'accès RGPD')
            ->htmlTemplate('email/data_export.html.twig')
            ->context([
                'recipientName' => $recipient->getFirstName() ?: $recipient->getUsername(),
                'exportDate' => (new \DateTimeImmutable())->format('d/m/Y'),
            ]);

        // Attach JSON file
        $email->addPart(
            new DataPart($jsonData, 'maeutic-data-export.json', 'application/json')
        );

        $this->mailer->send($email);
    }
}
