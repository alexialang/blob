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
}
