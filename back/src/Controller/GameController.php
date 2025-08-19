<?php

namespace App\Controller;

use App\Service\GameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/game')]
class GameController extends AbstractController
{
    public function __construct(
        private GameService $gameService,
        ) {}

    #[Route('/start/{quizId}', name: 'game_start', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function startGame(int $quizId): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $gameSession = $this->gameService->startGame($quizId, $user);
            return $this->json($gameSession);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/question/{sessionId}', name: 'game_get_question', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCurrentQuestion(string $sessionId): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $question = $this->gameService->getCurrentQuestion($sessionId, $user);
            return $this->json($question);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/answer/{sessionId}', name: 'game_submit_answer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function submitAnswer(Request $request, string $sessionId): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            $result = $this->gameService->submitAnswer($sessionId, $user, $data);
            return $this->json($result);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/results/{sessionId}', name: 'game_get_results', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getResults(string $sessionId): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $results = $this->gameService->getResults($sessionId, $user);
            return $this->json($results, 200, [
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/finish/{sessionId}', name: 'game_finish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function finishGame(string $sessionId): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $results = $this->gameService->finishGame($sessionId, $user);
            return $this->json($results, 200, [
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/status/{sessionId}', name: 'game_status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getGameStatus(string $sessionId): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            $status = $this->gameService->getGameStatus($sessionId, $user);
            return $this->json($status);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

}