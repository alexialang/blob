<?php

namespace App\Entity;

use App\Enum\Permission;
use App\Repository\UserPermissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserPermissionRepository::class)]
class UserPermission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'user_permission:read', 'user:admin_read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', enumType: Permission::class)]
    #[Groups(['user:read', 'user_permission:read', 'user:admin_read'])]
    private ?Permission $permission = null;

    #[ORM\ManyToOne(inversedBy: 'userPermissions')]
    #[Groups(['user:read', 'user_permission:read', 'user:admin_read'])]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPermission(): ?Permission
    {
        return $this->permission;
    }

    public function setPermission(Permission $permission): static
    {
        $this->permission = $permission;

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
}
