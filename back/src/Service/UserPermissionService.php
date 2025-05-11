<?php

namespace App\Service;

use App\Entity\UserPermission;
use App\Repository\UserPermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\Permission;

class UserPermissionService
{
    private EntityManagerInterface $em;
    private UserPermissionRepository $userPermissionRepository;
    private UserService $userService;

    public function __construct(
        EntityManagerInterface $em,
        UserPermissionRepository $userPermissionRepository,
        UserService $userService
    )

    {
        $this->em = $em;
        $this->userPermissionRepository = $userPermissionRepository;
        $this->userService = $userService;
    }

    public function list(): array
    {
        return $this->userPermissionRepository->findAll();
    }

    public function find(int $id): ?UserPermission
    {
        return $this->userPermissionRepository->find($id);
    }

    public function create(array $data): UserPermission
    {
        $userPermission = new UserPermission();
        $userPermission->setPermission(Permission::from($data['permission']));

        $user = $this->userService->find($data['user_id']);
        $userPermission->setUser($user);

        $this->em->persist($userPermission);
        $this->em->flush();

        return $userPermission;
    }

    public function update(UserPermission $userPermission, array $data): UserPermission
    {
        if (isset($data['permission'])) {
            $userPermission->setPermission(Permission::from($data['permission']));
        }
        if (isset($data['user_id'])) {
            $user = $this->userService->find($data['user_id']);
            $userPermission->setUser($user);
        }

        $this->em->flush();

        return $userPermission;
    }

    public function delete(UserPermission $userPermission): void
    {
        $this->em->remove($userPermission);
        $this->em->flush();
    }
}
