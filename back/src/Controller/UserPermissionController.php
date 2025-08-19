<?php

namespace App\Controller;

use App\Entity\UserPermission;
use App\Service\UserPermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/user-permission')]
class UserPermissionController extends AbstractController
{
    public function __construct(
        private UserPermissionService $userPermissionService,
        ) {}

    /**
     * @OA\Get(summary="Lister les permissions utilisateur", tags={"UserPermission"})
     * @OA\Response(response=200, description="Liste des permissions")
     */
    #[Route('/', name: 'user_permission_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $permissions = $this->userPermissionService->list();

        return $this->json($permissions, 200, [], ['groups' => ['user_permission:read']]);
    }

    /**
     * @OA\Post(summary="Créer une permission utilisateur", tags={"UserPermission"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="permission", type="string"),
     *         @OA\Property(property="user_id", type="integer")
     *     )
     * )
     * @OA\Response(response=201, description="Permission créée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'user_permission_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            $permission = $this->userPermissionService->create($data);

            return $this->json($permission, 201, [], ['groups' => ['user_permission:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher une permission utilisateur", tags={"UserPermission"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Détails de la permission")
     */
    #[Route('/{id}', name: 'user_permission_show', methods: ['GET'])]
    public function show(UserPermission $userPermission): JsonResponse
    {
        return $this->json($userPermission, 200, [], ['groups' => ['user_permission:read']]);
    }

    /**
     * @OA\Put(summary="Modifier une permission utilisateur", tags={"UserPermission"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="permission", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Permission modifiée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'user_permission_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, UserPermission $userPermission): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            $permission = $this->userPermissionService->update($userPermission, $data);

            return $this->json($permission, 200, [], ['groups' => ['user_permission:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer une permission utilisateur", tags={"UserPermission"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Permission supprimée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'user_permission_delete', methods: ['DELETE'])]
    public function delete(UserPermission $userPermission): JsonResponse
    {
        $this->userPermissionService->delete($userPermission);

        return $this->json(null, 204);
    }
}
