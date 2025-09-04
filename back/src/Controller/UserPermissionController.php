<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPermission;
use App\Repository\UserRepository;
use App\Service\UserPermissionService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
        private UserService $userService,
        private UserRepository $userRepository,
        ) {}

    /**
     * @OA\Get(summary="Lister les permissions utilisateur", tags={"UserPermission"})
     * @OA\Response(response=200, description="Liste des permissions")
     */
    #[Route('/', name: 'user_permission_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $permissions = $this->userPermissionService->list();

        return $this->json($permissions, 200, [], ['groups' => ['user_permission']]);
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
    #[IsGranted('MANAGE_USERS')]
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
    #[IsGranted('MANAGE_USERS', subject: 'userPermission')]
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
    #[IsGranted('MANAGE_USERS', subject: 'userPermission')]
    public function delete(UserPermission $userPermission): JsonResponse
    {
        $this->userPermissionService->delete($userPermission);

        return $this->json(null, 204);
    }

    /**
     * @OA\Put(summary="Mettre à jour les rôles et permissions d'un utilisateur", tags={"UserPermission"})
     * @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *     )
     * )
     * @OA\Response(response=200, description="Rôles et permissions mis à jour")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/{userId}', name: 'user_permission_update_user_roles', methods: ['PUT'])]
    #[IsGranted('MANAGE_USERS')]
    public function updateUserRoles(Request $request, int $userId): JsonResponse
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non trouvé'], 404);
            }
            
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            if (!isset($data['roles']) || !is_array($data['roles'])) {
                return $this->json(['error' => 'Roles array is required'], 400);
            }
            
            $allowedRoles = ['ROLE_USER', 'ROLE_ADMIN'];
            $validRoles = array_intersect($data['roles'], $allowedRoles);
            
            if (count($validRoles) !== count($data['roles'])) {
                return $this->json(['error' => 'Certains rôles ne sont pas autorisés'], 400);
            }
            
            $user->setRoles($validRoles);
            
            if (!isset($data['permissions']) || !is_array($data['permissions'])) {
                return $this->json(['error' => 'Permissions array is required'], 400);
            }
            
            $currentUser = $this->getUser();
            
            $user = $this->userService->updateUserRoles($user, $data);

            return $this->json($user, 200, [], [
                'groups' => ['user:roles_update']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
