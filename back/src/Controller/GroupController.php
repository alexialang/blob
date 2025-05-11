<?php

namespace App\Controller;

use App\Entity\Group;
use App\Service\GroupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/group')]
class GroupController extends AbstractController
{
    private GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * @OA\Get(summary="Lister les groupes", tags={"Group"})
     * @OA\Response(response=200, description="Liste des groupes")
     */
    #[Route('/', name: 'group_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $groups = $this->groupService->list();

        return $this->json($groups, 200, [], ['groups' => ['group:read']]);
    }

    /**
     * @OA\Post(summary="Créer un groupe", tags={"Group"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="acces_code", type="string"),
     *         @OA\Property(property="company_id", type="integer")
     *     )
     * )
     * @OA\Response(response=201, description="Groupe créé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'group_create', methods: ['POST'])]
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
}
