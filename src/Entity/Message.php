<?php

/**
 * Entité Message - Représente un message dans une conversation
 *
 * Cette entité gère les messages de chat :
 * - Contenu chiffré pour la sécurité
 * - Contenu en clair temporaire en mémoire
 * - Expéditeur du message
 * - Date et heure d'envoi
 * - Association optionnelle avec une conversation
 */

namespace App\Entity;

use App\Repository\MessageRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $encryptedContent = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?User $sender = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $sentAt = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    private ?Conversation $conversation = null;

    private ?string $content = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEncryptedContent(): ?string
    {
        return $this->encryptedContent;
    }

    public function setEncryptedContent(string $encryptedContent): static
    {
        $this->encryptedContent = $encryptedContent;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getSentAt(): ?DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;
        return $this;
    }
}
