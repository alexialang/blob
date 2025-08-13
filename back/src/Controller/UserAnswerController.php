<?php

namespace App\Controller;

use App\Entity\UserAnswer;
use App\Service\UserAnswerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/user-answer')]
class UserAnswerController extends AbstractController
{
    private UserAnswerService $userAnswerService;

    public function __construct(UserAnswerService $userAnswerService)
    {
        $this->userAnswerService = $userAnswerService;
    }

    /**
     * @OA\Get(summary="Lister toutes les réponses utilisateur", tags={"UserAnswer"})
     * @OA\Response(response=200, description="Liste des réponses")
     */
    #[Route('/', name: 'user_answer_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $userAnswers = $this->userAnswerService->list();

        return $this->json($userAnswers, 200, [], ['groups' => ['user_answer:read']]);
    }

    /**
     * @OA\Post(summary="Créer une réponse utilisateur", tags={"UserAnswer"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="quiz_id", type="integer"),
     *         @OA\Property(property="total_score", type="integer")
     *     )
     * )
     * @OA\Response(response=201, description="Réponse créée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'user_answer_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $userAnswer = $this->userAnswerService->create($data);

            return $this->json($userAnswer, 201, [], ['groups' => ['user_answer:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher une réponse utilisateur", tags={"UserAnswer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Détails d'une réponse")
     */
    #[Route('/{id}', name: 'user_answer_show', methods: ['GET'])]
    public function show(UserAnswer $userAnswer): JsonResponse
    {
        $userAnswer = $this->userAnswerService->show($userAnswer);

        return $this->json($userAnswer, 200, [], ['groups' => ['user_answer:read']]);
    }

    /**
     * @OA\Put(summary="Modifier une réponse utilisateur", tags={"UserAnswer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="total_score", type="integer")
     *     )
     * )
     * @OA\Response(response=200, description="Réponse modifiée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'user_answer_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, UserAnswer $userAnswer): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $userAnswer = $this->userAnswerService->update($userAnswer, $data);

            return $this->json($userAnswer, 200, [], ['groups' => ['user_answer:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer une réponse utilisateur", tags={"UserAnswer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Réponse supprimée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'user_answer_delete', methods: ['DELETE'])]
    public function delete(UserAnswer $userAnswer): JsonResponse
    {
        $this->userAnswerService->delete($userAnswer);

        return $this->json(null, 204);
    }

    /**
     * @OA\Post(summary="Sauvegarder le résultat d'un jeu", tags={"UserAnswer"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="quiz_id", type="integer"),
     *         @OA\Property(property="total_score", type="integer")
     *     )
     * )
     * @OA\Response(response=201, description="Résultat de jeu sauvegardé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/game-result', name: 'user_answer_save_game_result', methods: ['POST'])]
    public function saveGameResult(Request $request): JsonResponse
    {
        error_log("=== DEBUT saveGameResult ===");
        error_log("Request content: " . $request->getContent());
        
        $user = $this->getUser();
        error_log("User authenticated: " . ($user ? "YES (ID: {$user->getId()})" : "NO"));
        
        if (!$user) {
            error_log("ERREUR: Utilisateur non authentifié");
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            error_log("Data parsed: " . json_encode($data));
            
            // Ajouter l'utilisateur aux données
            $data['user'] = $user;
            
            $userAnswer = $this->userAnswerService->saveGameResult($data);
            error_log("UserAnswer created with ID: " . $userAnswer->getId());

            return $this->json([
                'message' => 'Résultat de jeu sauvegardé',
                'id' => $userAnswer->getId(),
                'score' => $userAnswer->getTotalScore(),
                'quiz_id' => $userAnswer->getQuiz()->getId(),
                'user_id' => $user->getId()
            ], 201);
        } catch (\JsonException $e) {
            error_log("JSON Error: " . $e->getMessage());
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Exception $e) {
            error_log("Exception: " . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(summary="Test d'authentification", tags={"UserAnswer"})
     * @OA\Response(response=200, description="Test réussi")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/test-auth', name: 'user_answer_test_auth', methods: ['GET'])]
    public function testAuth(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        return $this->json([
            'message' => 'Authentification OK',
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail()
        ]);
    }

    /**
     * @OA\Post(summary="Noter un quiz", tags={"UserAnswer"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="quizId", type="integer"),
     *         @OA\Property(property="rating", type="integer")
     *     )
     * )
     * @OA\Response(response=200, description="Note sauvegardée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/rate-quiz', name: 'user_answer_rate_quiz', methods: ['POST'])]
    public function rateQuiz(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            if (!isset($data['quizId']) || !isset($data['rating'])) {
                return $this->json(['error' => 'Données manquantes'], 400);
            }

            $data['user'] = $user;
            $result = $this->userAnswerService->rateQuiz($data);

            return $this->json($result, 200);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(summary="Classement d'un quiz", tags={"UserAnswer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Classement du quiz")
     */
    #[Route('/leaderboard/quiz/{id}', name: 'user_answer_quiz_leaderboard', methods: ['GET'])]
    public function getQuizLeaderboard(int $id): JsonResponse
    {
        try {
            $result = $this->userAnswerService->getQuizLeaderboard($id, $this->getUser());
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(summary="Récupérer les notes d'un quiz", tags={"UserAnswer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Notes du quiz")
     */
    #[Route('/quiz/{id}/rating', name: 'user_answer_quiz_rating', methods: ['GET'])]
    public function getQuizRating(int $id): JsonResponse
    {
        try {
            $result = $this->userAnswerService->getQuizRating($id, $this->getUser());
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
