<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\NotificationRepository;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $type;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, name: 'recipient_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $recipient;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, name: 'sender_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?User $sender = null;

    #[ORM\Column(type: 'string', length: 32)]
    private string $status = 'pending'; // pending | accepted | declined

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'pending';
        $this->isRead = false;
    }

    public function getId(): ?int { return $this->id; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getData(): ?array { return $this->data; }
    public function setData(?array $data): self { $this->data = $data; return $this; }

    public function getRecipient(): User { return $this->recipient; }
    public function setRecipient(User $recipient): self { $this->recipient = $recipient; return $this; }

    public function getSender(): ?User { return $this->sender; }
    public function setSender(?User $sender): self { $this->sender = $sender; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $dt): self { $this->createdAt = $dt; return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $v): self { $this->isRead = $v; return $this; }
}
