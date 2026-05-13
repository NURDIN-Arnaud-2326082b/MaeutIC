<?php

/**
 * Author entity — représente un auteur dans la bibliothèque.
 *
 * Modèle actuel :
 * - `firstName` / `lastName` (séparés) — accessible via `getFirstName()`/`setFirstName()`,
 *   et utilitaires `getName()` / `setName()` pour lecture/écriture conviviale.
 * - `birthYear` / `deathYear` (nullable)
 * - `nationality` (nullable)
 * - `image` (nom de fichier local ou URL externe)
 * - `user` (relation vers l'utilisateur qui a ajouté l'auteur)
 * - `books` (relation ManyToMany)
 * - Biographie :
 *   - `bioContent` (texte libre, nullable)
 *   - `bioUrl` (URL externe valide, nullable)
 *   - `bioPdfPath` (nom de fichier PDF uploadé, nullable)
 *
 * Notes :
 * - `getName()` compose `firstName` + `lastName` et retourne `null` si vide.
 * - `setName()` répartit une chaîne en `firstName`/`lastName` (heuristique simple).
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
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $birthYear = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $deathYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nationality = null;

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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bioContent = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $bioUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bioPdfPath = null;

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
        $fullName = trim((string) ($this->firstName ?? '') . ' ' . (string) ($this->lastName ?? ''));
        return $fullName !== '' ? $fullName : null;
    }

    public function setName(string $name): static
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) <= 1) {
            $this->firstName = $parts[0] ?? '';
            $this->lastName = $parts[0] ?? '';
            return $this;
        }

        $this->lastName = array_pop($parts) ?: '';
        $this->firstName = implode(' ', $parts);

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = trim($firstName);

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = trim($lastName);

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

    public function getBioContent(): ?string
    {
        return $this->bioContent;
    }

    public function setBioContent(?string $bioContent): static
    {
        $this->bioContent = $bioContent;

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
}
