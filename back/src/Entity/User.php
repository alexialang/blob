<?php

namespace App\Entity;

use App\Enum\Permission;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read','quiz:read'])]
    private ?int $id = null;
    #[Groups(['user:read', 'company:read','quiz:read'])]
    #[ORM\Column(length: 180)]
    private ?string $email = null;

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

    #[Groups(['user:read'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $dateRegistration = null;

    #[Groups(['user:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastAccess = null;


    #[Groups(['user:read'])]
    #[ORM\Column]
    private ?bool $isAdmin = null;

    #[Groups(['user:read'])]
    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Company $company = null;

    /**
     * @var Collection<int, Badge>
     */
    #[Groups(['user:read'])]
    #[ORM\ManyToMany(targetEntity: Badge::class, inversedBy: 'users')]
    private Collection $badges;

    /**
     * @var Collection<int, Quiz>
     */
    #[Groups(['user:read'])]
    #[ORM\OneToMany(targetEntity: Quiz::class, mappedBy: 'user')]
    private Collection $quizs;

    /**
     * @var Collection<int, UserAnswer>
     */
    #[Groups(['user:read'])]
    #[ORM\OneToMany(targetEntity: UserAnswer::class, mappedBy: 'user')]
    private Collection $userAnswers;

    /**
     * @var Collection<int, UserPermission>
     */
    #[Groups(['user:read'])]
    #[ORM\OneToMany(targetEntity: UserPermission::class, mappedBy: 'user')]
    private Collection $userPermissions;

    /**
     * @var Collection<int, Group>
     */
    #[Groups(['user:read'])]
    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'users')]
    private Collection $groups;

    #[ORM\Column(length: 70)]
    private ?string $firstName = null;

    #[ORM\Column(length: 70)]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->badges = new ArrayCollection();
        $this->quizs = new ArrayCollection();
        $this->userAnswers = new ArrayCollection();
        $this->userPermissions = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
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

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {

    }

    public function getDateRegistration(): ?\DateTimeImmutable
    {
        return $this->dateRegistration;
    }

    public function setDateRegistration(\DateTimeImmutable $dateRegistration): static
    {
        $this->dateRegistration = $dateRegistration;

        return $this;
    }

    public function getLastAccess(): ?\DateTimeInterface
    {
        return $this->lastAccess;
    }

    public function setLastAccess(?\DateTimeInterface $lastAccess): static
    {
        $this->lastAcces = $lastAccess;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): static
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return Collection<int, Badge>
     */
    public function getBadges(): Collection
    {
        return $this->badges;
    }

    public function addBadge(Badge $badge): static
    {
        if (!$this->badges->contains($badge)) {
            $this->badges->add($badge);
        }

        return $this;
    }

    public function removeBadge(Badge $badge): static
    {
        $this->badges->removeElement($badge);

        return $this;
    }

    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizs(): Collection
    {
        return $this->quizs;
    }

    public function addQuiz(Quiz $quiz): static
    {
        if (!$this->quizs->contains($quiz)) {
            $this->quizs->add($quiz);
            $quiz->setUser($this);
        }

        return $this;
    }

    public function removeQuiz(Quiz $quiz): static
    {
        if ($this->quizs->removeElement($quiz)) {
            // set the owning side to null (unless already changed)
            if ($quiz->getUser() === $this) {
                $quiz->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserAnswer>
     */
    public function getUserAnswers(): Collection
    {
        return $this->userAnswers;
    }

    public function addUserAnswer(UserAnswer $userAnswer): static
    {
        if (!$this->userAnswers->contains($userAnswer)) {
            $this->userAnswers->add($userAnswer);
            $userAnswer->setUser($this);
        }

        return $this;
    }

    public function removeUserAnswer(UserAnswer $userAnswer): static
    {
        if ($this->userAnswers->removeElement($userAnswer)) {
            // set the owning side to null (unless already changed)
            if ($userAnswer->getUser() === $this) {
                $userAnswer->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserPermission>
     */
    public function getUserPermissions(): Collection
    {
        return $this->userPermissions;
    }

    public function addUserPermission(UserPermission $userPermission): static
    {
        if (!$this->userPermissions->contains($userPermission)) {
            $this->userPermissions->add($userPermission);
            $userPermission->setUser($this);
        }

        return $this;
    }

    public function removeUserPermission(UserPermission $userPermission): static
    {
        if ($this->userPermissions->removeElement($userPermission)) {
            // set the owning side to null (unless already changed)
            if ($userPermission->getUser() === $this) {
                $userPermission->setUser(null);
            }
        }

        return $this;
    }
    public function hasPermission(Permission $permission): bool
    {
        foreach ($this->userPermissions as $perm) {
            if ($perm->getPermission() === $permission) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }

        return $this;
    }

    public function removeGroup(Group $group): static
    {
        $this->groups->removeElement($group);

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }


}
