<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class UserController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/user', name: 'user_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userService->list(false);

        return $this->json($users, 200, [], [
            'groups' => ['user:read', 'company:read', 'user_permission:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
    }

    #[Route('/admin/all', name: 'admin_user_list', methods: ['GET'])]
    public function adminList(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $this->userService->list(true);

        return $this->json($users, 200, [], ['groups' => ['user:admin_read']]);
    }

    #[Route('/user-create', name: 'user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            if (isset($data['recaptchaToken'])) {
                if (!$this->userService->verifyCaptcha($data['recaptchaToken'])) {
                    return $this->json(['error' => 'Échec de la vérification CAPTCHA'], 400);
                }
            }
            
            $user = $this->userService->create($data);

            return $this->json($user, 201, [], ['groups' => ['user:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('user/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[Route('user/{id}', name: 'user_update', methods: ['PUT', 'PATCH'])]
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

    #[Route('user/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return $this->json(null, 204);
    }
    
    #[Route('/user/profile', name: 'user_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        return $this->json($user, 200, [], [
            'groups' => ['user:read', 'company:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
    }

    #[Route('/confirmation-compte/{token}', name: 'user_confirm_account', methods: ['GET'])]
    public function confirmAccount(string $token): JsonResponse
    {
        $user = $this->userService->confirmToken($token);

        if (!$user) {
            return $this->json(['error' => 'Token invalide ou déjà utilisé'], 400);
        }

        return $this->json(['message' => 'Votre compte a bien été vérifié']);
    }

    #[Route('/user/profile/update', name: 'user_profile_update', methods: ['PUT', 'PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $user = $this->userService->updateProfile($user, $data);

            return $this->json($user, 200, [], [
                'groups' => ['user:read', 'company:read'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/user/statistics', name: 'user_statistics', methods: ['GET'])]
    public function statistics(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $statistics = $this->userService->getUserStatistics($user);

        return $this->json($statistics);
    }

    #[Route('/leaderboard', name: 'user_leaderboard', methods: ['GET'])]
    public function leaderboard(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 50);
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $leaderboard = $this->userService->getLeaderboard($limit, $currentUser);

        return $this->json($leaderboard);
    }

    #[Route('/user/game-history', name: 'user_game_history', methods: ['GET'])]
    public function gameHistory(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $history = $this->userService->getGameHistory($user);

        return $this->json($history);
    }

    #[Route('/user/{id}/roles', name: 'user_update_roles', methods: ['PUT'])]
    public function updateRoles(Request $request, User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            if (!isset($data['roles']) || !is_array($data['roles'])) {
                return $this->json(['error' => 'Roles array is required'], 400);
            }
            if (!isset($data['permissions']) || !is_array($data['permissions'])) {
                return $this->json(['error' => 'Permissions array is required'], 400);
            }
            
            $user = $this->userService->updateUserRoles($user, $data);

            return $this->json($user, 200, [], [
                'groups' => ['user:read', 'user_permission:read'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

}
