<?php

namespace App\Controller;

use App\Entity\UserAnswer;
use App\Service\UserAnswerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user-answer')]
class UserAnswerController extends AbstractController
{
    private UserAnswerService $userAnswerService;

    public function __construct(UserAnswerService $userAnswerService)
    {
        $this->userAnswerService = $userAnswerService;
    }

    #[Route('/', name: 'user_answer_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $userAnswers = $this->userAnswerService->list();

        return $this->json($userAnswers, 200, [], [
            'groups' => ['user_answer:read']
        ]);
    }

    #[Route('/', name: 'user_answer_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $userAnswer = $this->userAnswerService->create($data);

            return $this->json($userAnswer, 201, [], [
                'groups' => ['user_answer:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'user_answer_show', methods: ['GET'])]
    public function show(UserAnswer $userAnswer): JsonResponse
    {
        $userAnswer = $this->userAnswerService->show($userAnswer);

        return $this->json($userAnswer, 200, [], [
            'groups' => ['user_answer:read']
        ]);
    }

    #[Route('/{id}', name: 'user_answer_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, UserAnswer $userAnswer): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $userAnswer = $this->userAnswerService->update($userAnswer, $data);

            return $this->json($userAnswer, 200, [], [
                'groups' => ['user_answer:read']
            ]);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
    }

    #[Route('/{id}', name: 'user_answer_delete', methods: ['DELETE'])]
    public function delete(UserAnswer $userAnswer): JsonResponse
    {
        $this->userAnswerService->delete($userAnswer);

        return $this->json(null, 204);
    }
}
