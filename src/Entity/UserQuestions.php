<?php

/**
 * Entité UserQuestions - Représente les réponses aux questions du profil
 *
 * Cette entité stocke les réponses des utilisateurs aux questions d'inscription :
 * - Questions dynamiques personnalisées sur leur parcours de chercheur
 * - Questions taggables
 * - Association avec l'utilisateur
 * - Suppression en cascade si l'utilisateur est supprimé
 */

namespace App\Entity;

use App\Repository\UserQuestionsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserQuestionsRepository::class)]
class UserQuestions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 200)]
    private ?string $question = null;

    #[ORM\Column(type: 'string', length: 5000)]
    private ?string $answer = null;

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

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): static
    {
        $this->answer = $answer;

        return $this;
    }
}
