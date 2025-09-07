<?php

namespace App\Service;

use App\Entity\UserPermission;
use App\Enum\Permission;
use App\Repository\UserPermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserPermissionService
{
    private EntityManagerInterface $em;
    private UserPermissionRepository $userPermissionRepository;
    private UserService $userService;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $em,
        UserPermissionRepository $userPermissionRepository,
        UserService $userService,
        ValidatorInterface $validator,
    ) {
        $this->em = $em;
        $this->userPermissionRepository = $userPermissionRepository;
        $this->userService = $userService;
        $this->validator = $validator;
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
        $this->validateUserPermissionData($data);

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
        $this->validateUserPermissionData($data);

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

    private function validateUserPermissionData(array $data): void
    {
        $constraints = new Assert\Collection([
            'fields' => [
                'permission' => [
                    new Assert\NotBlank(['message' => 'La permission est requise']),
                    new Assert\Length(['max' => 100, 'maxMessage' => 'La permission ne peut pas dépasser 100 caractères']),
                ],
                'user_id' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'integer', 'message' => 'L\'ID de l\'utilisateur doit être un entier']),
                    ]),
                ],
            ],
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }
}
