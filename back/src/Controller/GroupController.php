<?php

namespace App\Controller;

use App\Entity\Group;
use App\Service\GroupService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Annotations as OA;

#[Route('/api/group')]
class GroupController extends AbstractController
{
    private GroupService $groupService;
    private UserService $userService;

    public function __construct(GroupService $groupService, UserService $userService)
    {
        $this->groupService = $groupService;
        $this->userService = $userService;
    }

    /**
     * @OA\Get(summary="Lister les groupes", tags={"Group"})
     * @OA\Response(response=200, description="Liste des groupes")
     */
    #[Route('', name: 'group_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $groups = $this->groupService->list();

        return $this->json($groups, 200, [], ['groups' => ['group:read']]);
    }

    /**
     * @OA\Get(summary="Lister les groupes", tags={"Group"})
     * @OA\Response(response=200, description="Liste des groupes")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/list', name: 'group_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $groups = $this->groupService->getGroupsByUser($user);
        
        return $this->json($groups, 200, [], ['groups' => ['group:read']]);
    }

    /**
     * @OA\Post(summary="Créer un groupe", tags={"Group"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="acces_code", type="string"),
     *         @OA\Property(property="company_id", type="integer"),
     *         @OA\Property(property="member_ids", type="array", @OA\Items(type="integer"))
     *     )
     * )
     * @OA\Response(response=201, description="Groupe créé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('', name: 'group_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $group = $this->groupService->create($data);

            return $this->json($group, 201, [], ['groups' => ['group:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher un groupe", tags={"Group"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Détails du groupe")
     */
    #[Route('/{id}', name: 'group_show', methods: ['GET'])]
    public function show(Group $group): JsonResponse
    {
        return $this->json($group, 200, [], ['groups' => ['group:read']]);
    }

    /**
     * @OA\Put(summary="Modifier un groupe", tags={"Group"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="acces_code", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Groupe modifié")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'group_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Group $group): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $group = $this->groupService->update($group, $data);

            return $this->json($group, 200, [], ['groups' => ['group:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer un groupe", tags={"Group"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Groupe supprimé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'group_delete', methods: ['DELETE'])]
    public function delete(Group $group): JsonResponse
    {
        $this->groupService->delete($group);

        return $this->json(null, 204);
    }

    /**
     * @OA\Post(summary="Ajouter un utilisateur à un groupe", tags={"Group"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="user_id", type="integer")
     *     )
     * )
     * @OA\Response(response=200, description="Utilisateur ajouté au groupe")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}/add-user', name: 'group_add_user', methods: ['POST'])]
    public function addUser(Request $request, Group $group): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $userId = $data['user_id'] ?? null;

            if (!$userId) {
                return $this->json(['error' => 'user_id is required'], 400);
            }

            $user = $this->userService->find($userId);
            if (!$user) {
                return $this->json(['error' => 'User not found'], 404);
            }

            $this->groupService->addUserToGroup($group, $user);

            return $this->json(['message' => 'User added to group successfully'], 200);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Delete(summary="Retirer un utilisateur d'un groupe", tags={"Group"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Utilisateur retiré du groupe")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}/remove-user/{userId}', name: 'group_remove_user', methods: ['DELETE'])]
    public function removeUser(Group $group, int $userId): JsonResponse
    {
        $user = $this->userService->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $this->groupService->removeUserFromGroup($group, $user);

        return $this->json(['message' => 'User removed from group successfully'], 200);
    }
}
