<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Service\QuizService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/quiz')]
class QuizController extends AbstractController
{
    private QuizService $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    #[Route('/', name: 'quiz_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $quizList = $this->quizService->list();

        return $this->json($quizList, 200, [], [
            'groups' => ['quiz:read']
        ]);
    }

    /**
     * @throws \JsonException
     */
    #[Route('/', name: 'quiz_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $quiz = $this->quizService->create($data);

        return $this->json($quiz, 201);
    }

    #[Route('/{id}', name: 'quiz_show', methods: ['GET'])]
    public function show(Quiz $quiz): JsonResponse
    {
        $quiz = $this->quizService->show($quiz);

        return $this->json($quiz);
    }

    /**
     * @throws \JsonException
     */
    #[Route('/{id}', name: 'quiz_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Quiz $quiz): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $quiz = $this->quizService->update($quiz, $data);

        return $this->json($quiz);
    }

    #[Route('/{id}', name: 'quiz_delete', methods: ['DELETE'])]
    public function delete(Quiz $quiz): JsonResponse
    {
        $this->quizService->delete($quiz);

        return $this->json(null, 204);
    }
}
