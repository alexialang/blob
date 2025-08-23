<?php

namespace App\Controller;

use App\Entity\Group;
use App\Service\GroupService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @OA\Tag(name="Group")
 */
#[Route('/api/group')]
class GroupController extends AbstractController
{
    public function __construct(
        private GroupService $groupService,
        private UserService $userService,
    ) {}
    /**
     * @OA\Get(summary="Lister les groupes (gestion complète)", tags={"Group"})
     * @OA\Response(response=200, description="Liste des groupes")
     */
    #[Route('', name: 'group_index', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS')]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user->isAdmin()) {
            $userCompany = $user->getCompany();
            if (!$userCompany) {
                return $this->json(['error' => 'Vous n\'appartenez à aucune entreprise'], 403);
            }
            
            $groups = $this->groupService->getGroupsByCompany($userCompany);
        } else {
            $groups = $this->groupService->list();
        }

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
    #[IsGranted('MANAGE_USERS')]
    public function create(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $data = json_decode($request->getContent(), true);
            
            if (!$user->isAdmin()) {
                $userCompany = $user->getCompany();
                if (!$userCompany) {
                    return $this->json(['error' => 'Vous n\'appartenez à aucune entreprise'], 403);
                }
                
                $data['company_id'] = $userCompany->getId();
                
                $group = $this->groupService->createForCompany($data, $userCompany);
            } else {
                $group = $this->groupService->create($data);
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Groupe créé avec succès',
                'data' => [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'accesCode' => $group->getAccesCode(),
                    'companyId' => $group->getCompany() ? $group->getCompany()->getId() : null
                ]
            ], 201);
            
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json([
                'success' => false,
                'message' => 'Données invalides',
                'details' => $errorMessages
            ], 400);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création du groupe: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Delete(summary="Supprimer un groupe", tags={"Group"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Groupe supprimé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'group_delete', methods: ['DELETE'])]
    #[IsGranted('MANAGE_USERS', subject: 'group')]
    public function delete(Group $group): JsonResponse
    {
        try {
            $this->groupService->delete($group);
            
            return $this->json([
                'success' => true,
                'message' => 'Groupe supprimé avec succès'
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du groupe: ' . $e->getMessage()
            ], 500);
        }
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
    #[IsGranted('MANAGE_USERS', subject: 'group')]
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
    #[IsGranted('MANAGE_USERS', subject: 'group')]
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