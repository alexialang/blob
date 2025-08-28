<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use App\Service\LeaderboardService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;
use OpenApi\Annotations as OA;

#[Route('/api')]
class UserController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private LeaderboardService $leaderboardService,
        private LoggerInterface $logger
    ) {}



    /**
     * @OA\Get(summary="Lister tous les utilisateurs (admin)", tags={"User"})
     * @OA\Response(response=200, description="Liste complète des utilisateurs")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/admin/all', name: 'admin_user_list', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS')]
    public function adminList(): JsonResponse
    {
        $currentUser = $this->getUser();
        $users = $this->userService->listWithStats(true, $currentUser);

        return $this->json($users, 200, [], [
            'groups' => ['user:admin_read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
    }



    /**
     * @OA\Post(summary="Créer un nouvel utilisateur", tags={"User"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         required={"firstName", "lastName", "email", "password", "recaptchaToken"},
     *         @OA\Property(property="firstName", type="string"),
     *         @OA\Property(property="lastName", type="string"),
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="recaptchaToken", type="string", description="Token CAPTCHA obligatoire")
     *     )
     * )
     * @OA\Response(response=201, description="Utilisateur créé")
     */
    #[Route('/user-create', name: 'user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            if (!isset($data['recaptchaToken']) || empty($data['recaptchaToken'])) {
                return $this->json(['error' => 'Token CAPTCHA requis'], 400);
            }
            
                        if (!$this->userService->verifyCaptcha($data['recaptchaToken'])) {
                return $this->json(['error' => 'Échec de la vérification CAPTCHA'], 400);
            }
            
            unset($data['recaptchaToken']);
            
            $user = $this->userService->create($data);

            return $this->json($user, 201, [], ['groups' => ['user:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création d\'utilisateur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->json(['error' => 'Erreur lors de la création de l\'utilisateur'], 500);
        }
    }

    /**
     * @OA\Get(summary="Récupérer le profil utilisateur connecté", tags={"User"})
     * @OA\Response(response=200, description="Profil utilisateur")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/profile', name: 'user_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du profil utilisateur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'error' => 'Erreur serveur lors de la récupération du profil',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(summary="Mettre à jour le profil utilisateur", tags={"User"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="firstName", type="string"),
     *         @OA\Property(property="lastName", type="string"),
     *         @OA\Property(property="email", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Profil mis à jour")
     * @OA\Security(name="bearerAuth")
     */
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
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du profil utilisateur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->getId()
            ]);
            
            return $this->json([
                'error' => 'Erreur serveur lors de la mise à jour du profil',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(summary="Statistiques de l'utilisateur connecté", tags={"User"})
     * @OA\Response(response=200, description="Statistiques utilisateur")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/statistics', name: 'user_statistics', methods: ['GET'])]
    public function statistics(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $statistics = $this->userService->getUserStatistics($user);

        return $this->json($statistics, 200, [], [
            'groups' => ['user:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
    }

    /**
     * @OA\Get(summary="Statistiques d'un utilisateur par ID", tags={"User"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Statistiques de l'utilisateur")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/{id}/statistics', name: 'user_statistics_by_id', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[IsGranted('VIEW_RESULTS', subject: 'user')]
    public function statisticsById(User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $statistics = $this->userService->getUserStatistics($user);

        return $this->json($statistics, 200, [], [
            'groups' => ['user:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
    }

    /**
     * @OA\Get(summary="Afficher un utilisateur", tags={"User"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Utilisateur affiché")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/{id}', name: 'user_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[IsGranted('VIEW_RESULTS', subject: 'user')]
    public function show(User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }
        $this->logger->info('Affichage utilisateur', [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'company' => $user->getCompany() ? $user->getCompany()->getId() : 'null'
        ]);

        return $this->json($user, 200, [], [
            'groups' => ['user:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
    }

    /**
     * @OA\Put(summary="Mettre à jour un utilisateur", tags={"User"})
     * @OA\Patch(summary="Mettre à jour un utilisateur (partiel)", tags={"User"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(type="object")
     * )
     * @OA\Response(response=200, description="Utilisateur mis à jour")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/{id}', name: 'user_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('MANAGE_USERS', subject: 'user')]
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $currentUser = $this->getUser();
            
            $user = $this->userService->update($user, $data, $currentUser);

            return $this->json($user, 200, [], [
                'groups' => ['user:read'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Patch(summary="Anonymiser un utilisateur", tags={"User"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Utilisateur anonymisé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/{id}/anonymize', name: 'user_anonymize', methods: ['PATCH'])]
    #[IsGranted('MANAGE_USERS', subject: 'user')]
    public function anonymize(User $user): JsonResponse
    {
        $currentUser = $this->getUser();
        
        $this->logger->warning('SECURITY: Tentative d\'anonymisation d\'utilisateur', [
            'admin_user_id' => $currentUser->getId(),
            'admin_user_email' => $currentUser->getEmail(),
            'target_user_id' => $user->getId(),
            'target_user_email' => $user->getEmail(),
            'timestamp' => new \DateTime()
        ]);
        
        $this->userService->anonymizeUser($user, $currentUser);

        return $this->json([
            'success' => true,
            'message' => 'Utilisateur anonymisé avec succès'
        ]);
    }
    
    /**
     * @OA\Get(summary="Confirmer un compte par token", tags={"User"})
     * @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string"))
     * @OA\Response(response=200, description="Compte confirmé")
     */
    #[Route('/confirmation-compte/{token}', name: 'user_confirm_account', methods: ['GET'])]
    public function confirmAccount(string $token): JsonResponse
    {
        $user = $this->userService->confirmToken($token);

        if (!$user) {
            return $this->json(['error' => 'Token invalide ou déjà utilisé'], 400);
        }

        return $this->json(['message' => 'Votre compte a bien été vérifié']);
    }

    /**
     * @OA\Get(summary="Classement général des utilisateurs", tags={"User"})
     * @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Classement général")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/leaderboard', name: 'user_leaderboard', methods: ['GET'])]
    public function leaderboard(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 50);
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $leaderboard = $this->leaderboardService->getGeneralLeaderboard($limit, $currentUser);

        return $this->json($leaderboard);
    }

    /**
     * @OA\Get(summary="Historique des parties de l'utilisateur connecté", tags={"User"})
     * @OA\Response(response=200, description="Historique des parties")
     * @OA\Security(name="bearerAuth")
     */
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

    /**
     * @OA\Put(summary="Mettre à jour les rôles et permissions d'un utilisateur", tags={"User"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *     )
     * )
     * @OA\Response(response=200, description="Rôles mis à jour")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/{id}/roles', name: 'user_update_roles', methods: ['PUT'])]
    #[IsGranted('MANAGE_USERS', subject: 'user')]
    public function updateRoles(Request $request, User $user): JsonResponse
    {
        try {
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
            
        $this->logger->warning('SECURITY: Modification des rôles utilisateur', [
            'admin_user_id' => $currentUser->getId(),
            'admin_user_email' => $currentUser->getEmail(),
            'target_user_id' => $user->getId(),
            'target_user_email' => $user->getEmail(),
            'new_roles' => $validRoles,
            'new_permissions' => $data['permissions'],
            'timestamp' => new \DateTime()
        ]);
            
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
