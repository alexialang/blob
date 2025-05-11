<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/user')]
class UserController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @OA\Get(summary="Lister tous les utilisateurs", tags={"User"})
     * @OA\Response(response=200, description="Liste des utilisateurs")
     */
    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userService->list();

        return $this->json($users, 200, [], ['groups' => ['user:read']]);
    }

    /**
     * @OA\Post(summary="Créer un utilisateur", tags={"User"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="company_id", type="integer", nullable=true)
     *     )
     * )
     * @OA\Response(response=201, description="Utilisateur créé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $user = $this->userService->create($data);

            return $this->json($user, 201, [], ['groups' => ['user:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher un utilisateur", tags={"User"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Détails de l'utilisateur")
     */
    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    /**
     * @OA\Put(summary="Modifier un utilisateur", tags={"User"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="is_admin", type="boolean")
     *     )
     * )
     * @OA\Response(response=200, description="Utilisateur modifié")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'user_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $user = $this->userService->update($user, $data);

            return $this->json($user, 200, [], ['groups' => ['user:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer un utilisateur", tags={"User"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Utilisateur supprimé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return $this->json(null, 204);
    }
}
