<?php

/**
 * Entité Author - Représente un auteur dans la bibliothèque
 *
 * Cette entité gère les auteurs référencés dans la bibliothèque académique :
 * - Nom de l'auteur
 * - Années de naissance et décès
 * - Nationalité
 * - Lien vers une ressource externe
 * - Photo/image de l'auteur
 * - Tags associés pour catégorisation
 * - Utilisateur ayant ajouté l'auteur
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $birthYear = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $deathYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nationality = null;

    // `link` renamed to `bioUrl` (database column added by migration)

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?User $user = null;

    /**
     * @var File|null
     */
    #[Assert\File(
        maxSize: "4M",
        mimeTypes: ["image/jpeg", "image/png", "image/webp"],
        mimeTypesMessage: "Merci d'uploader une image valide (JPEG, PNG, WEBP)"
    )]
    private $imageFile;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\ManyToMany(targetEntity: Book::class, mappedBy: 'authors')]
    private Collection $books;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $bioType = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $bioUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bioPdfPath = null;

    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Article $bioArticle = null;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBirthYear(): ?int
    {
        return $this->birthYear;
    }

    public function setBirthYear(?int $birthYear): static
    {
        $this->birthYear = $birthYear;

        return $this;
    }

    public function getDeathYear(): ?int
    {
        return $this->deathYear;
    }

    public function setDeathYear(?int $deathYear): static
    {
        $this->deathYear = $deathYear;

        return $this;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): static
    {
        $this->nationality = $nationality;

        return $this;
    }

    // `link` accessor removed — use `getBioUrl()` / `setBioUrl()` instead

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
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

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $file): static
    {
        $this->imageFile = $file;
        return $this;
    }

    /**
     * @return Collection<int, Book>
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): static
    {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
            $book->addAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): static
    {
        if ($this->books->removeElement($book)) {
            $book->removeAuthor($this);
        }

        return $this;
    }

    public function getBioType(): ?string
    {
        return $this->bioType;
    }

    public function setBioType(?string $bioType): static
    {
        $this->bioType = $bioType;

        return $this;
    }

    public function getBioUrl(): ?string
    {
        return $this->bioUrl;
    }

    public function setBioUrl(?string $bioUrl): static
    {
        $this->bioUrl = $bioUrl;

        return $this;
    }

    public function getBioPdfPath(): ?string
    {
        return $this->bioPdfPath;
    }

    public function setBioPdfPath(?string $bioPdfPath): static
    {
        $this->bioPdfPath = $bioPdfPath;

        return $this;
    }

    public function getBioArticle(): ?Article
    {
        return $this->bioArticle;
    }

    public function setBioArticle(?Article $bioArticle): static
    {
        $this->bioArticle = $bioArticle;

        return $this;
    }
}
