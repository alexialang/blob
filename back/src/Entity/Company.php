<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['quiz:read','company:read'])]
    private ?int $id = null;


    #[Groups(['quiz:read','company:read'])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Groups(['company:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    /**
     * @var Collection<int, User>
     */
    #[Groups(['company:read'])]
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'company')]
    private Collection $users;

    /**
     * @var Collection<int, Group>
     */
    #[Groups(['company:read'])]
    #[ORM\OneToMany(targetEntity: Group::class, mappedBy: 'company')]
    private Collection $groups;

    /**
     * @var Collection<int, Quiz>
     */
    #[ORM\OneToMany(targetEntity: Quiz::class, mappedBy: 'company')]
    private Collection $quizs;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->quizs = new ArrayCollection();
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

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setCompany($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getCompany() === $this) {
                $user->setCompany(null);
            }
        }

        return $this;
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
            $group->setCompany($this);
        }

        return $this;
    }

    public function removeGroup(Group $group): static
    {
        if ($this->groups->removeElement($group)) {
            if ($group->getCompany() === $this) {
                $group->setCompany(null);
            }
        }

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
            $quiz->setCompany($this);
        }

        return $this;
    }

    public function removeQuiz(Quiz $quiz): static
    {
        if ($this->quizs->removeElement($quiz)) {
            if ($quiz->getCompany() === $this) {
                $quiz->setCompany(null);
            }
        }

        return $this;
    }
}
