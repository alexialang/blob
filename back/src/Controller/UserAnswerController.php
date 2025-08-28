<?php

namespace App\Controller;

use App\Entity\UserAnswer;
use App\Service\UserAnswerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Annotations as OA;

#[Route('/api/user-answer')]
class UserAnswerController extends AbstractController
{
    public function __construct(
        private UserAnswerService $userAnswerService,
        ) {}

    /**
     * @OA\Get(summary="Lister les réponses utilisateur", tags={"UserAnswer"})
     * @OA\Response(response=200, description="Liste des réponses utilisateur")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'user_answer_index', methods: ['GET'])]
    #[IsGranted('VIEW_RESULTS')]
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
     *         @OA\Property(property="question_id", type="integer"),
     *         @OA\Property(property="answer_id", type="integer"),
     *         @OA\Property(property="score", type="integer")
     *     )
     * )
     * @OA\Response(response=201, description="Réponse utilisateur créée")
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
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher une réponse utilisateur", tags={"UserAnswer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Réponse utilisateur affichée")
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
     *         @OA\Property(property="question_id", type="integer"),
     *         @OA\Property(property="answer_id", type="integer"),
     *         @OA\Property(property="score", type="integer")
     *     )
     * )
     * @OA\Response(response=200, description="Réponse utilisateur modifiée")
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
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer une réponse utilisateur", tags={"UserAnswer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Réponse utilisateur supprimée")
     */
    #[Route('/{id}', name: 'user_answer_delete', methods: ['DELETE'])]
    public function delete(UserAnswer $userAnswer): JsonResponse
    {
        $this->userAnswerService->delete($userAnswer);

        return $this->json(null, 204);
    }

    /**
     * @OA\Post(summary="Sauvegarder un résultat de jeu", tags={"UserAnswer"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="quiz_id", type="integer"),
     *         @OA\Property(property="total_score", type="integer"),
     *         @OA\Property(property="answers", type="array", @OA\Items(type="object"))
     *     )
     * )
     * @OA\Response(response=201, description="Résultat de jeu sauvegardé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/game-result', name: 'user_answer_save_game_result', methods: ['POST'])]
    public function saveGameResult(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            $data['user'] = $user;
            
            $userAnswer = $this->userAnswerService->saveGameResult($data);

            return $this->json([
                'message' => 'Résultat de jeu sauvegardé',
                'id' => $userAnswer->getId(),
                'score' => $userAnswer->getTotalScore(),
                'quiz_id' => $userAnswer->getQuiz()->getId(),
                'user_id' => $user->getId()
            ], 201);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }



    /**
     * @OA\Post(summary="Noter un quiz", tags={"UserAnswer"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="quizId", type="integer"),
     *         @OA\Property(property="rating", type="integer")
     *     )
     * )
     * @OA\Response(response=200, description="Quiz noté")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/rate-quiz', name: 'user_answer_rate_quiz', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
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
     * @OA\Get(summary="Note d'un quiz", tags={"UserAnswer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Note du quiz")
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