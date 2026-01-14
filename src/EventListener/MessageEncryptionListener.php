<?php

namespace App\EventListener;

use App\Entity\Message;
use App\Service\EncryptionService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Exception;

#[AsEntityListener(event: Events::prePersist, entity: Message::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Message::class)]
#[AsEntityListener(event: Events::postLoad, entity: Message::class)]
class MessageEncryptionListener
{
    public function __construct(
        private EncryptionService $encryptionService
    )
    {
    }

    public function prePersist(Message $message, LifecycleEventArgs $event): void
    {
        $this->encryptContent($message);
    }

    private function encryptContent(Message $message): void
    {
        if ($content = $message->getContent()) {
            $encrypted = $this->encryptionService->encrypt($content);
            $message->setEncryptedContent($encrypted);
        }
    }

    public function preUpdate(Message $message, LifecycleEventArgs $event): void
    {
        $this->encryptContent($message);
    }

    public function postLoad(Message $message, LifecycleEventArgs $event): void
    {
        $this->decryptContent($message);
    }

    private function decryptContent(Message $message): void
    {
        if ($encrypted = $message->getEncryptedContent()) {
            try {
                $decrypted = $this->encryptionService->decrypt($encrypted);
                $message->setContent($decrypted);
            } catch (Exception $e) {
                $message->setContent('[Message chiffré - impossible à déchiffrer]');
            }
        }
    }
}