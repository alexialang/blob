<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Service\AnswerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/answer')]
class AnswerController extends AbstractController
{
    private AnswerService $answerService;

    public function __construct(AnswerService $answerService)
    {
        $this->answerService = $answerService;
    }

    #[Route('/', name: 'answer_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $answerList = $this->answerService->list();

        return $this->json($answerList, 200, [], [
            'groups' => ['answer:read']
        ]);
    }

    #[Route('/', name: 'answer_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $answer = $this->answerService->create($data);

            return $this->json($answer, 201, [], [
                'groups' => ['answer:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'answer_show', methods: ['GET'])]
    public function show(Answer $answer): JsonResponse
    {
        $answer = $this->answerService->show($answer);

        return $this->json($answer, 200, [], [
            'groups' => ['answer:read']
        ]);
    }

    #[Route('/{id}', name: 'answer_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Answer $answer): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $answer = $this->answerService->update($answer, $data);

            return $this->json($answer, 200, [], [
                'groups' => ['answer:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'answer_delete', methods: ['DELETE'])]
    public function delete(Answer $answer): JsonResponse
    {
        $this->answerService->delete($answer);

        return $this->json(null, 204);
    }
}