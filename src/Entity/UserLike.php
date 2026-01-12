<?php

/**
 * Entité UserLike - Représente un like sur un commentaire
 *
 * Cette entité gère les likes donnés par les utilisateurs sur des commentaires :
 * - Association avec l'utilisateur qui like
 * - Association avec le commentaire liké
 * - Suppression en cascade si l'utilisateur ou le commentaire est supprimé
 */

namespace App\Entity;

use App\Repository\LikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LikeRepository::class)]
class UserLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'user_likes')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Comment::class, inversedBy: 'user_likes')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Comment $comment = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}
