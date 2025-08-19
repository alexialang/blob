<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Service\AnswerService;
use App\Service\InputSanitizerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/answer')]
class AnswerController extends AbstractController
{
    public function __construct(
        private AnswerService $answerService,
        private InputSanitizerService $inputSanitizer
    ) {}

    /**
     * @OA\Get(summary="Lister toutes les réponses", tags={"Answer"})
     * @OA\Response(response=200, description="Liste des réponses")
     */
    #[Route('/', name: 'answer_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $answerList = $this->answerService->list();

        return $this->json($answerList, 200, [], ['groups' => ['answer:read']]);
    }

    /**
     * @OA\Post(summary="Créer une réponse", tags={"Answer"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="answer", type="string"),
     *         @OA\Property(property="is_correct", type="boolean"),
     *         @OA\Property(property="order_correct", type="string"),
     *         @OA\Property(property="pair_id", type="string"),
     *         @OA\Property(property="is_intrus", type="boolean"),
     *         @OA\Property(property="question_id", type="integer")
     *     )
     * )
     * @OA\Response(response=201, description="Réponse créée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'answer_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            // ✅ SANITISATION : Nettoyer les entrées utilisateur
            $sanitizedData = $this->inputSanitizer->sanitizeAnswerData($data);
            
            $answer = $this->answerService->create($sanitizedData);

            return $this->json($answer, 201, [], ['groups' => ['answer:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher une réponse", tags={"Answer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Réponse affichée")
     */
    #[Route('/{id}', name: 'answer_show', methods: ['GET'])]
    public function show(Answer $answer): JsonResponse
    {
        return $this->json($answer, 200, [], ['groups' => ['answer:read']]);
    }

    /**
     * @OA\Put(summary="Modifier une réponse", tags={"Answer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="answer", type="string"),
     *         @OA\Property(property="is_correct", type="boolean"),
     *         @OA\Property(property="order_correct", type="string"),
     *         @OA\Property(property="pair_id", type="string"),
     *         @OA\Property(property="is_intrus", type="boolean")
     *     )
     * )
     * @OA\Response(response=200, description="Réponse modifiée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'answer_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Answer $answer): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            $sanitizedData = $this->inputSanitizer->sanitizeAnswerData($data);
            
            $answer = $this->answerService->update($answer, $sanitizedData);

            return $this->json($answer, 200, [], ['groups' => ['answer:read']]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer une réponse", tags={"Answer"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Réponse supprimée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'answer_delete', methods: ['DELETE'])]
    public function delete(Answer $answer): JsonResponse
    {
        $this->answerService->delete($answer);
        return $this->json(null, 204);
    }
}
