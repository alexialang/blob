<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Service\QuizService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Annotations as OA;

#[Route('/api/quiz')]
class QuizController extends AbstractController
{
    private QuizService $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    /**
     * @OA\Get(summary="Liste des quiz", tags={"Quiz"})
     * @OA\Response(response=200, description="Liste des quiz")
     */
    #[Route('/', name: 'quiz_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $quizList = $this->quizService->list();

        return $this->json($quizList, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Post(summary="Créer un quiz", tags={"Quiz"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="status", type="string"),
     *         @OA\Property(property="category", type="integer")
     *     )
     * )
     * @OA\Response(response=201, description="Quiz créé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/', name: 'quiz_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $quiz = $this->quizService->create($data, $user);

        return $this->json($quiz, 201, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Get(summary="Afficher un quiz par ID", tags={"Quiz"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Détails du quiz")
     */
    #[Route('/{id}', name: 'quiz_show', methods: ['GET'])]
    public function show(Quiz $quiz): JsonResponse
    {
        $quiz = $this->quizService->show($quiz);

        return $this->json($quiz, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Put(summary="Modifier un quiz", tags={"Quiz"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="status", type="string"),
     *         @OA\Property(property="category", type="integer")
     *     )
     * )
     * @OA\Response(response=200, description="Quiz modifié")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'quiz_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request, Quiz $quiz): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $quiz = $this->quizService->update($quiz, $data);

        return $this->json($quiz, 200, [], ['groups' => ['quiz:read']]);
    }

    /**
     * @OA\Delete(summary="Supprimer un quiz", tags={"Quiz"})
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=204, description="Quiz supprimé")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/{id}', name: 'quiz_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Quiz $quiz): JsonResponse
    {
        $this->quizService->delete($quiz);

        return $this->json(null, 204);
    }
}
