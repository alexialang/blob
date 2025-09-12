<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[Route('/api')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @OA\Get(summary="Lister tous les utilisateurs (admin)", tags={"User"})
     *
     * @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1))
     * @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", default=20))
     * @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string"))
     * @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"id", "email", "firstName", "lastName", "dateRegistration"}))
     *
     * @OA\Response(response=200, description="Liste paginée des utilisateurs")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/admin/all', name: 'admin_user_list', methods: ['GET'])]
    #[IsGranted('MANAGE_USERS')]
    public function adminList(Request $request): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20))); // Max 100 par page
        $search = $request->query->get('search');
        $sort = $request->query->get('sort', 'id');

        $result = $this->userService->listWithStatsAndPagination(true, $currentUser, $page, $limit, $search, $sort);

        return $this->json($result, 200, [], [
            'groups' => ['user:admin_list'],
        ]);
    }

    /**
     * @OA\Post(summary="Créer un nouvel utilisateur", tags={"User"})
     *
     * @OA\RequestBody(
     *     required=true,
     *
     *     @OA\JsonContent(
     *         required={"firstName", "lastName", "email", "password", "recaptchaToken"},
     *
     *         @OA\Property(property="firstName", type="string"),
     *         @OA\Property(property="lastName", type="string"),
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="recaptchaToken", type="string", description="Token CAPTCHA obligatoire")
     *     )
     * )
     *
     * @OA\Response(response=201, description="Utilisateur créé")
     */
    #[Route('/user-create', name: 'user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['recaptchaToken']) || empty($data['recaptchaToken'])) {
                return $this->json(['error' => 'Token reCAPTCHA manquant'], 400);
            }

            if (!$this->userService->verifyCaptcha($data['recaptchaToken'], 'register')) {
                return $this->json(['error' => 'Échec de la vérification CAPTCHA'], 400);
            }

            unset($data['recaptchaToken']);

            $user = $this->userService->create($data);

            return $this->json($user, 201, [], ['groups' => ['user:read']]);
        } catch (\JsonException $e) {
            $this->logger->error('Erreur JSON dans create(): '.$e->getMessage());

            return $this->json(['error' => 'Format JSON invalide'], 400);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }

            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Argument invalide dans create(): '.$e->getMessage());

            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->logger->error('Erreur inattendue dans create(): '.$e->getMessage());

            return $this->json(['error' => 'Erreur lors de la création de l\'utilisateur'], 500);
        }
    }

    /**
     * @OA\Get(summary="Récupérer le profil utilisateur connecté", tags={"User"})
     *
     * @OA\Response(response=200, description="Profil utilisateur")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/profile', name: 'user_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            return $this->json($user, 200, [], [
                'groups' => ['user:profile'],
            ]);
        } catch (\Exception) {
            return $this->json([
                'error' => 'Erreur lors de la récupération du profil',
            ], 500);
        }
    }

    /**
     * @OA\Put(summary="Mettre à jour le profil utilisateur", tags={"User"})
     *
     * @OA\RequestBody(
     *     required=true,
     *
     *     @OA\JsonContent(
     *
     *         @OA\Property(property="firstName", type="string"),
     *         @OA\Property(property="lastName", type="string"),
     *         @OA\Property(property="email", type="string")
     *     )
     * )
     *
     * @OA\Response(response=200, description="Profil mis à jour")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/profile/update', name: 'user_profile_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $user = $this->userService->updateProfile($user, $data);

            return $this->json($user, 200, [], [
                'groups' => ['user:profile'],
            ]);
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }

            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour du profil',
            ], 500);
        }
    }

    /**
     * @OA\Get(summary="Statistiques de l'utilisateur connecté", tags={"User"})
     *
     * @OA\Response(response=200, description="Statistiques utilisateur")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/statistics', name: 'user_statistics', methods: ['GET'])]
    public function statistics(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $statistics = $this->userService->getUserStatistics($user);

            return $this->json($statistics, 200, [], [
                'groups' => ['user:statistics'],
            ]);
        } catch (\Exception) {
            return $this->json([
                'error' => 'Erreur lors du calcul des statistiques',
            ], 500);
        }
    }

    /**
     * @OA\Get(summary="Statistiques d'un utilisateur par ID", tags={"User"})
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Statistiques de l'utilisateur")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/{id}/statistics', name: 'user_statistics_by_id', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[IsGranted('VIEW_RESULTS', subject: 'user')]
    public function statisticsById(User $user): JsonResponse
    {
        $statistics = $this->userService->getUserStatistics($user);

        return $this->json($statistics, 200, [], [
            'groups' => ['user:statistics'],
        ]);
    }

    /**
     * @OA\Get(summary="Afficher un utilisateur", tags={"User"})
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Utilisateur affiché")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/{id}', name: 'user_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[IsGranted('VIEW_RESULTS', subject: 'user')]
    public function show(User $user): JsonResponse
    {
        return $this->json($user, 200, [], [
            'groups' => ['user:profile'],
        ]);
    }

    /**
     * @OA\Put(summary="Mettre à jour un utilisateur", tags={"User"})
     *
     * @OA\Patch(summary="Mettre à jour un utilisateur (partiel)", tags={"User"})
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\RequestBody(
     *     required=true,
     *
     *     @OA\JsonContent(type="object")
     * )
     *
     * @OA\Response(response=200, description="Utilisateur mis à jour")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/{id}', name: 'user_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('MANAGE_USERS', subject: 'user')]
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            // ✅ Délégation complète au service (validation incluse)
            /** @var User|null $currentUser */
            $currentUser = $this->getUser();
            $user = $this->userService->update($user, $data, $currentUser);

            return $this->json($user, 200, [], [
                'groups' => ['user:profile'],
            ]);
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }

            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception) {
            return $this->json(['error' => 'Erreur lors de la mise à jour'], 500);
        }
    }

    /**
     * @OA\Patch(summary="Anonymiser un utilisateur", tags={"User"})
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Utilisateur anonymisé")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/{id}/anonymize', name: 'user_anonymize', methods: ['PATCH'])]
    #[IsGranted('MANAGE_USERS', subject: 'user')]
    public function anonymize(User $user): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        $this->userService->anonymizeUser($user, $currentUser);

        return $this->json([
            'success' => true,
            'message' => 'Utilisateur anonymisé avec succès',
        ]);
    }

    /**
     * @OA\Get(summary="Confirmer un compte par token", tags={"User"})
     *
     * @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string"))
     *
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
     * @OA\Get(summary="Historique des parties de l'utilisateur connecté", tags={"User"})
     *
     * @OA\Response(response=200, description="Historique des parties")
     *
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/user/game-history', name: 'user_game_history', methods: ['GET'])]
    public function gameHistory(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $history = $this->userService->getGameHistory($user);

        return $this->json($history);
    }
}
