<?php

/**
 * Entité Forum - Représente un forum de discussion
 *
 * Cette entité définit les forums :
 * - Titre et description du forum
 * - Type de forum
 * - Date de dernière activité
 * - Collection de posts associés
 * - Options spéciales
 */

namespace App\Entity;

use App\Repository\ForumRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumRepository::class)]
class Forum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 500)]
    private ?string $title = null;

    #[ORM\Column(length: 5000)]
    private ?string $body = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $anonymous = false;

    #[ORM\Column(name: "debussy_clairDeLune", type: 'boolean', options: ['default' => false])]
    private bool $debussy_clairDeLune = false;

    #[ORM\Column(length: 255, options: ['default' => 'general'])]
    private ?string $special = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $lastActivity = null;

    #[ORM\OneToMany(mappedBy: 'forum', targetEntity: Post::class, orphanRemoval: true)]
    private Collection $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getLastActivity(): ?DateTimeInterface
    {
        return $this->lastActivity;
    }

    public function setLastActivity(DateTimeInterface $lastActivity): static
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }

    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }

    public function setAnonymous(bool $anonymous): static
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    public function isDebussyClairDeLune(): bool
    {
        return $this->debussy_clairDeLune;
    }

    public function setDebussyClairDeLune(bool $debussy_clairDeLune): static
    {
        $this->debussy_clairDeLune = $debussy_clairDeLune;

        return $this;
    }

    public function getSpecial(): ?string
    {
        return $this->special;
    }

    public function setSpecial(string $special): static
    {
        $this->special = $special;

        return $this;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setForum($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getForum() === $this) {
                $post->setForum(null);
            }
        }

        return $this;
    }
}