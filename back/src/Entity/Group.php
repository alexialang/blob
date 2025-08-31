<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['company:read', 'group:read', 'quiz:read', 'quiz:create', 'user:admin_read', 'company:detail', 'group:create'])]
    private ?int $id = null;

    #[Groups(['user:read', 'company:read', 'group:read', 'quiz:read', 'quiz:create', 'user:admin_read', 'company:detail', 'group:create'])]
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:admin_read', 'company:detail', 'group:create'])]
    private ?string $acces_code = null;

    #[ORM\ManyToOne(inversedBy: 'groups')]
    private ?Company $company = null;

    /**
     * @var Collection<int, User>
     */
    #[Groups(['company:read', 'group:read', 'company:detail'])]
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'groups')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
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

    public function getAccesCode(): ?string
    {
        return $this->acces_code;
    }

    public function setAccesCode(?string $acces_code): static
    {
        $this->acces_code = $acces_code;

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
            $user->addGroup($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeGroup($this);
        }

        return $this;
    }

    #[Groups(['company:detail'])]
    public function getUserCount(): int
    {
        return $this->users->count();
    }
}
