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
    #[Groups(['user:read','quiz:read','company:read','user:admin_read','user:public','company:detail'])]
    private ?int $id = null;
    #[Groups(['user:read', 'company:read','quiz:read','user:admin_read','user:public','company:detail'])]
    #[ORM\Column(length: 180)]
    private ?string $email = null;


    #[Groups(['user:read','quiz:read','user:admin_read'])]
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[Groups(['user:read','user:admin_read'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $dateRegistration = null;

    #[Groups(['user:read','user:admin_read','company:available_users'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastAccess = null;




    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(['user:read', 'user:admin_read'])]
    private ?Company $company = null;

    #[Groups(['user:read','user:admin_read'])]
    #[ORM\ManyToMany(targetEntity: Badge::class, inversedBy: 'users')]
    private Collection $badges;

    #[Groups(['user:read','user:admin_read'])]
    #[ORM\OneToMany(targetEntity: Quiz::class, mappedBy: 'user')]
    private Collection $quizs;

    #[Groups(['user:read','user:admin_read'])]
    #[ORM\OneToMany(targetEntity: UserAnswer::class, mappedBy: 'user')]
    private Collection $userAnswers;

    #[Groups(['user:read','user:admin_read'])]
    #[ORM\OneToMany(targetEntity: UserPermission::class, mappedBy: 'user')]
    private Collection $userPermissions;

    #[Groups(['user:read','user:admin_read'])]
    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'users')]
    private Collection $groups;

    #[Groups(['user:read','user:admin_read','user:public','company:detail'])]
    #[ORM\Column(length: 70)]
    private ?string $firstName = null;
    #[Groups(['user:read','user:admin_read','user:public','company:detail'])]
    #[ORM\Column(length: 70)]
    private ?string $lastName = null;

    #[Groups(['user:read','user:admin_read','company:detail','company:available_users'])]
    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $confirmationToken = null;

    #[Groups(['company:available_users'])]
    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordResetRequestAt = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $pseudo = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

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
        $this->lastAccess = $lastAccess;

        return $this;
    }

    #[Groups(['user:read'])]
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
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
            if ($quiz->getUser() === $this) {
                $quiz->setUser(null);
            }
        }

        return $this;
    }

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
            if ($userAnswer->getUser() === $this) {
                $userAnswer->setUser(null);
            }
        }

        return $this;
    }

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
    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }
    public function setConfirmationToken(?string $confirmationToken): static
    {
        $this->confirmationToken = $confirmationToken;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }
    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): static
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    public function getPasswordResetRequestAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetRequestAt;
    }

    public function setPasswordResetRequestAt(?\DateTimeImmutable $passwordResetRequestAt): static
    {
        $this->passwordResetRequestAt = $passwordResetRequestAt;

        return $this;
    }

    #[Groups(['user:read', 'company:available_users'])]
    public function getCompanyName(): ?string
    {
        return $this->company?->getName();
    }

    #[Groups(['user:read', 'company:available_users'])]
    public function getCompanyId(): ?int
    {
        return $this->company?->getId();
    }

    #[Groups(['user:read', 'company:available_users'])]
    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): static
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    #[Groups(['user:read', 'company:available_users'])]
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    #[Groups(['user:read'])]
    public function getAvatarShape(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        
        $avatarData = json_decode($this->avatar, true);
        return $avatarData['shape'] ?? null;
    }

    #[Groups(['user:read'])]
    public function getAvatarColor(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        
        $avatarData = json_decode($this->avatar, true);
        return $avatarData['color'] ?? null;
    }
}
