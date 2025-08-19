<?php

namespace App\Controller;

use App\Entity\Question;
use App\Service\QuestionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/question')]
class QuestionController extends AbstractController
{
    public function __construct(
        private QuestionService $questionService,
        ) {}

    /**
     * @OA\Get(summary="Lister toutes les questions", tags={"Question"})
     * @OA\Response(response=200, description="Liste des questions")
     */
    #[Route('/', name: 'question_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $questionList = $this->questionService->list();

        return $this->json($questionList, 200, [], ['groups' => ['question:read']]);
    }

    /**
     * @OA\Post(summary="Créer une question", tags={"Question"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="question", type="string"),
     *         @OA\Property(property="quiz_id", type="integer"),
     *         @OA\Property(property="type_question_id", type="integer")
     *     )
     * )
     * @OA\Response(response=201, description="Question créée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'question_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $question = $this->questionService->create($data);

            return $this->json($question, 201, [], ['groups' => ['question:read']]);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        }
    }

    /**
     * @OA\Get(summary="Afficher une question", tags={"Question"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Détails de la question")
     */
    #[Route('/{id}', name: 'question_show', methods: ['GET'])]
    public function show(Question $question): JsonResponse
    {
        $question = $this->questionService->show($question);

        return $this->json($question, 200, [], ['groups' => ['question:read']]);
    }

    /**
     * @OA\Put(summary="Modifier une question", tags={"Question"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="question", type="string"),
     *         @OA\Property(property="quiz_id", type="integer"),
     *         @OA\Property(property="type_question_id", type="integer")
     *     )
     * )
     * @OA\Response(response=200, description="Question modifiée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'question_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Question $question): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $question = $this->questionService->update($question, $data);

            return $this->json($question, 200, [], ['groups' => ['question:read']]);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        }
    }

    /**
     * @OA\Delete(summary="Supprimer une question", tags={"Question"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Question supprimée")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'question_delete', methods: ['DELETE'])]
    public function delete(Question $question): JsonResponse
    {
        $this->questionService->delete($question);

        return $this->json(null, 204);
    }
}