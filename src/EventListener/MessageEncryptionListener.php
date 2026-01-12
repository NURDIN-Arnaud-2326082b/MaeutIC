<?php

/**
 * Listener pour le chiffrement automatique des messages
 *
 * Intercepte les événements du cycle de vie des entités Message
 * pour automatiquement chiffrer/déchiffrer le contenu :
 *
 * - prePersist : Chiffre le contenu avant l'insertion en base
 * - preUpdate : Chiffre le contenu avant la mise à jour
 * - postLoad : Déchiffre le contenu après le chargement depuis la base
 *
 * Assure que les messages sont toujours chiffrés en base de données
 * mais accessibles en clair dans l'application
 */

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

    /**
     * Chiffre le contenu avant l'insertion en base
     *
     * @param Message $message L'entité Message en cours de persistance
     * @param LifecycleEventArgs $event Les arguments de l'événement
     * @return void
     */
    public function prePersist(Message $message, LifecycleEventArgs $event): void
    {
        $this->encryptContent($message);
    }

    /**
     * Chiffre le contenu du message
     *
     * @param Message $message L'entité Message à chiffrer
     * @return void
     */
    private function encryptContent(Message $message): void
    {
        if ($content = $message->getContent()) {
            $encrypted = $this->encryptionService->encrypt($content);
            $message->setEncryptedContent($encrypted);
        }
    }

    /**
     * Chiffre le contenu avant la mise à jour
     *
     * @param Message $message L'entité Message en cours de mise à jour
     * @param LifecycleEventArgs $event Les arguments de l'événement
     * @return void
     */
    public function preUpdate(Message $message, LifecycleEventArgs $event): void
    {
        $this->encryptContent($message);
    }

    /**
     * Déchiffre le contenu après chargement depuis la base
     *
     * @param Message $message L'entité Message chargée
     * @param LifecycleEventArgs $event Les arguments de l'événement
     * @return void
     */
    public function postLoad(Message $message, LifecycleEventArgs $event): void
    {
        $this->decryptContent($message);
    }

    /**
     * Déchiffre le contenu du message
     *
     * @param Message $message L'entité Message à déchiffrer
     * @return void
     */
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