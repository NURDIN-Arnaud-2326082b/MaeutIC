<?php

/**
 * Entité User - Représente un utilisateur de l'application MaeutIC
 *
 * Cette entité centrale gère toutes les informations d'un utilisateur :
 * - Authentification (email, username, mot de passe)
 * - Profil (nom, prénom, photo, affiliation, spécialisation)
 * - Réseau social (connexions, blocages)
 * - Relations avec posts, commentaires, likes
 * - Questions du profil chercheur
 * - Abonnements aux posts
 *
 * Implémente UserInterface et PasswordAuthenticatedUserInterface de Symfony Security
 */

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_ID', fields: ['id'])]
#[UniqueEntity(fields: ['id'], message: 'There is already an account with this id')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $affiliationLocation = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $specialization = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $researchTopic = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profileImage = null;

    #[Assert\File(
        maxSize: "2M",
        mimeTypes: ["image/jpeg", "image/png", "image/webp"],
        mimeTypesMessage: "Merci d'uploader une image valide (JPEG, PNG, WEBP)"
    )]
    private $profileImageFile;

    #[ORM\ManyToMany(targetEntity: Post::class, inversedBy: 'subscribedUsers')]
    #[ORM\JoinTable(name: 'Subscription')]
    private Collection $subscribedPosts;

    #[ORM\OneToMany(targetEntity: UserLike::class, mappedBy: 'user')]
    private Collection $user_likes;

    #[ORM\OneToMany(targetEntity: UserQuestions::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $userQuestions;

    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'userId')]
    private Collection $posts;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'userId')]
    private Collection $comments;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'integer')]
    private ?int $userType = 0;

    // Persist the network as JSON in the database
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $network = [];

    // Persist the blocked users as JSON in the database (list of user ids this user has blocked)
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $blocked = [];

    // Persist the users who blocked this user (list of user ids)
    // nom de colonne fixé pour matcher la migration qui a créé "blockedby"
    #[ORM\Column(name: 'blockedby', type: 'json', nullable: true)]
    private ?array $blockedBy = [];
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $genre = null;

    public function __construct()
    {
        $this->subscribedPosts = new ArrayCollection();
        $this->user_likes = new ArrayCollection();
        $this->userQuestions = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->id;
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * @return list<string>
     * @see UserInterface
     *
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Sets the hashed password.
     *
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Sets the email address.
     *
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Sets the username.
     *
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Sets the last name.
     *
     * @param string $lastName
     * @return $this
     */
    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Sets the first name.
     *
     * @param string $firstName
     * @return $this
     */
    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAffiliationLocation(): ?string
    {
        return $this->affiliationLocation;
    }

    /**
     * Sets the affiliation location.
     *
     * @param string|null $affiliationLocation
     * @return $this
     */
    public function setAffiliationLocation(?string $affiliationLocation): static
    {
        $this->affiliationLocation = $affiliationLocation;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSpecialization(): ?string
    {
        return $this->specialization;
    }

    /**
     * Sets the specialization.
     *
     * @param string|null $specialization
     * @return $this
     */
    public function setSpecialization(?string $specialization): static
    {
        $this->specialization = $specialization;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getResearchTopic(): ?string
    {
        return $this->researchTopic;
    }

    /**
     * Sets the research topic.
     *
     * @param string|null $researchTopic
     * @return $this
     */
    public function setResearchTopic(?string $researchTopic): static
    {
        $this->researchTopic = $researchTopic;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    /**
     * Sets the profile image path.
     *
     * @param string|null $profileImage
     * @return $this
     */
    public function setProfileImage(?string $profileImage): static
    {
        $this->profileImage = $profileImage;
        return $this;
    }

    /**
     * @return File|null
     */
    public function getProfileImageFile(): ?File
    {
        return $this->profileImageFile;
    }

    /**
     * Sets the profile image file.
     *
     * @param File|null $file
     * @return $this
     */
    public function setProfileImageFile(?File $file): static
    {
        $this->profileImageFile = $file;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getSubscribedPosts(): Collection
    {
        return $this->subscribedPosts;
    }

    /**
     * @param Post $Post
     * @return $this
     */
    public function addSubscribedPost(Post $Post): static
    {
        if (!$this->subscribedPosts->contains($Post)) {
            $this->subscribedPosts->add($Post);
        }

        return $this;
    }

    /**
     * @param Post $Post
     * @return $this
     */
    public function removeSubscribedPost(Post $Post): static
    {
        $this->subscribedPosts->removeElement($Post);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getUserLikes(): Collection
    {
        return $this->user_likes;
    }

    /**
     * @param UserLike $user_like
     * @return $this
     */
    public function addUserLike(UserLike $user_like): static
    {
        if (!$this->user_likes->contains($user_like)) {
            $this->user_likes->add($user_like);
            $user_like->setUser($this);
        }

        return $this;
    }

    /**
     * @param UserLike $user_like
     * @return $this
     */
    public function removeUserLike(UserLike $user_like): static
    {
        if ($this->user_likes->removeElement($user_like)) {
            // set the owning side to null (unless already changed)
            if ($user_like->getUser() === $this) {
                $user_like->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getUserQuestions(): Collection
    {
        return $this->userQuestions;
    }

    /**
     * @param UserQuestions $userQuestion
     * @return $this
     */
    public function addUserQuestion(UserQuestions $userQuestion): static
    {
        if (!$this->userQuestions->contains($userQuestion)) {
            $this->userQuestions->add($userQuestion);
            $userQuestion->setUser($this);
        }

        return $this;
    }

    /**
     * @param UserQuestions $userQuestion
     * @return $this
     */
    public function removeUserQuestion(UserQuestions $userQuestion): static
    {
        if ($this->userQuestions->removeElement($userQuestion)) {
            // set the owning side to null (unless already changed)
            if ($userQuestion->getUser() === $this) {
                $userQuestion->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * @param Post $post
     * @return $this
     */
    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setUser($this);
        }

        return $this;
    }

    /**
     * @param Post $post
     * @return $this
     */
    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getUser() === $this) {
                $post->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * @param Comment $comment
     * @return $this
     */
    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setUser($this);
        }

        return $this;
    }

    /**
     * @param Comment $comment
     * @return $this
     */
    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUserType(): ?int
    {
        return $this->userType;
    }

    /**
     * Sets the user type.
     *
     * @param int $userType
     * @return $this
     */
    public function setUserType(int $userType): static
    {
        $this->userType = $userType;

        return $this;
    }

    /**
     * @param int $userId
     * @return $this
     */
    public function addToNetwork(int $userId): self
    {
        $list = $this->getNetwork();
        if (!in_array($userId, $list, true)) {
            $list[] = $userId;
            $this->network = $list;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getNetwork(): array
    {
        return $this->network ?? [];
    }

    /**
     * Sets the network list.
     *
     * @param array $network
     * @return $this
     */
    public function setNetwork(array $network): self
    {
        $this->network = array_values(array_unique(array_map('intval', $network)));

        return $this;
    }

    /**
     * @param int $userId
     * @return $this
     */
    public function removeFromNetwork(int $userId): self
    {
        $list = $this->getNetwork();
        $list = array_values(array_filter($list, fn($id) => ((int)$id) !== $userId));
        $this->network = $list;

        return $this;
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function isInNetwork(int $userId): bool
    {
        return in_array($userId, $this->getNetwork(), true);
    }

    // --- Blocked users (similar API to network) ---

    /**
     * @param int $userId
     * @return $this
     */
    public function addToBlocked(int $userId): self
    {
        $list = $this->getBlocked();
        if (!in_array($userId, $list, true)) {
            $list[] = $userId;
            $this->blocked = $list;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getBlocked(): array
    {
        return $this->blocked ?? [];
    }

    /**
     * Sets the blocked users list.
     *
     * @param array $blocked
     * @return $this
     */
    public function setBlocked(array $blocked): self
    {
        $this->blocked = array_values(array_unique(array_map('intval', $blocked)));
        return $this;
    }

    /**
     * @param int $userId
     * @return $this
     */
    public function removeFromBlocked(int $userId): self
    {
        $list = $this->getBlocked();
        $list = array_values(array_filter($list, fn($id) => ((int)$id) !== $userId));
        $this->blocked = $list;
        return $this;
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function isBlocked(int $userId): bool
    {
        return in_array($userId, $this->getBlocked(), true);
    }

    // --- "blockedBy" API: users who have blocked this user ---

    /**
     * @param int $userId
     * @return $this
     */
    public function addToBlockedBy(int $userId): self
    {
        $list = $this->getBlockedBy();
        if (!in_array($userId, $list, true)) {
            $list[] = $userId;
            $this->blockedBy = $list;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getBlockedBy(): array
    {
        return $this->blockedBy ?? [];
    }

    /**
     * @param array $blockedBy
     * @return $this
     */
    public function setBlockedBy(array $blockedBy): self
    {
        $this->blockedBy = array_values(array_unique(array_map('intval', $blockedBy)));
        return $this;
    }

    /**
     * @param int $userId
     * @return $this
     */
    public function removeFromBlockedBy(int $userId): self
    {
        $list = $this->getBlockedBy();
        $list = array_values(array_filter($list, fn($id) => ((int)$id) !== $userId));
        $this->blockedBy = $list;
        return $this;
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function isBlockedBy(int $userId): bool
    {
        return in_array($userId, $this->getBlockedBy(), true);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return string|null
     */
    public function getGenre(): ?string
    {
        return $this->genre;
    }

    /**
     * @param string|null $genre
     * @return $this
     */
    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    // Méthode utilitaire pour afficher "chercheur" ou "chercheuse"

    /**
     * @return string
     */
    public function getResearcherTitle(): string
    {
        return match ($this->genre) {
            'female' => 'chercheuse',
            'male' => 'chercheur',
            'other' => 'chercheur·euse',
            default => 'chercheur·euse'
        };
    }
}