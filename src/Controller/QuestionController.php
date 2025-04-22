<?php

namespace App\Controller;

use App\Entity\Question;
use App\Service\QuestionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/question')]
class QuestionController extends AbstractController
{
    private QuestionService $questionService;

    public function __construct(QuestionService $questionService)
    {
        $this->questionService = $questionService;
    }

    #[Route('/', name: 'question_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $questionList = $this->questionService->list();

        return $this->json($questionList, 200, [], [
            'groups' => ['question:read']
        ]);
    }

    #[Route('/', name: 'question_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $question = $this->questionService->create($data);

        return $this->json($question, 201, [], [
            'groups' => ['question:read']
        ]);
    }

    #[Route('/{id}', name: 'question_show', methods: ['GET'])]
    public function show(Question $question): JsonResponse
    {
        $question = $this->questionService->show($question);

        return $this->json($question, 200, [], [
            'groups' => ['question:read']
        ]);
    }

    #[Route('/{id}', name: 'question_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Question $question): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $question = $this->questionService->update($question, $data);

        return $this->json($question, 200, [], [
            'groups' => ['question:read']
        ]);
    }

    #[Route('/{id}', name: 'question_delete', methods: ['DELETE'])]
    public function delete(Question $question): JsonResponse
    {
        $this->questionService->delete($question);

        return $this->json(null, 204);
    }
}
