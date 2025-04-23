<?php

namespace App\Controller;

use App\Entity\Group;
use App\Service\GroupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/group')]
class GroupController extends AbstractController
{
    private GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    #[Route('/', name: 'group_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $groups = $this->groupService->list();

        return $this->json($groups, 200, [], [
            'groups' => ['group:read']
        ]);
    }

    #[Route('/', name: 'group_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $group = $this->groupService->create($data);

            return $this->json($group, 201, [], [
                'groups' => ['group:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'group_show', methods: ['GET'])]
    public function show(Group $group): JsonResponse
    {
        return $this->json($group, 200, [], [
            'groups' => ['group:read']
        ]);
    }

    #[Route('/{id}', name: 'group_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Group $group): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $group = $this->groupService->update($group, $data);

            return $this->json($group, 200, [], [
                'groups' => ['group:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'group_delete', methods: ['DELETE'])]
    public function delete(Group $group): JsonResponse
    {
        $this->groupService->delete($group);

        return $this->json(null, 204);
    }
}
