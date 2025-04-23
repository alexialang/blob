<?php

namespace App\Controller;

use App\Entity\UserPermission;
use App\Service\UserPermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user-permission')]
class UserPermissionController extends AbstractController
{
    private UserPermissionService $userPermissionService;

    public function __construct(UserPermissionService $userPermissionService)
    {
        $this->userPermissionService = $userPermissionService;
    }

    #[Route('/', name: 'user_permission_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $permissions = $this->userPermissionService->list();

        return $this->json($permissions, 200, [], [
            'groups' => ['user_permission:read']
        ]);
    }

    #[Route('/', name: 'user_permission_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $permission = $this->userPermissionService->create($data);

            return $this->json($permission, 201, [], [
                'groups' => ['user_permission:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'user_permission_show', methods: ['GET'])]
    public function show(UserPermission $userPermission): JsonResponse
    {
        return $this->json($userPermission, 200, [], [
            'groups' => ['user_permission:read']
        ]);
    }

    #[Route('/{id}', name: 'user_permission_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, UserPermission $userPermission): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $permission = $this->userPermissionService->update($userPermission, $data);

            return $this->json($permission, 200, [], [
                'groups' => ['user_permission:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'user_permission_delete', methods: ['DELETE'])]
    public function delete(UserPermission $userPermission): JsonResponse
    {
        $this->userPermissionService->delete($userPermission);

        return $this->json(null, 204);
    }
}
