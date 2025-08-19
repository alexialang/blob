<?php

namespace App\Controller;

use App\Service\MultiplayerGameService;
use App\Service\InputSanitizerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/multiplayer')]
#[IsGranted('ROLE_USER')]
class MultiplayerGameController extends AbstractController
{
    public function __construct(
        private MultiplayerGameService $multiplayerService,
        private InputSanitizerService $inputSanitizer
    ) {
    }

    #[Route('/room/create', name: 'create_game_room', methods: ['POST'])]
    public function createRoom(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $sanitizedData = $this->inputSanitizer->sanitizeMultiplayerGameData($data);
            
            $user = $this->getUser();
            
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non connecté'], 401);
            }
            
            $quizId = $sanitizedData['quizId'] ?? 0;
            $maxPlayers = $sanitizedData['maxPlayers'] ?? 4;
            $isTeamMode = $sanitizedData['isTeamMode'] ?? false;
            $roomName = $sanitizedData['roomName'] ?? null;
            
            $room = $this->multiplayerService->createRoom(
                $user,
                $quizId,
                $maxPlayers,
                $isTeamMode,
                $roomName
            );

            return $this->json($room);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/room/{roomId}/join', name: 'join_game_room', methods: ['POST'])]
    public function joinRoom(string $roomId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $sanitizedData = $this->inputSanitizer->sanitizeMultiplayerGameData($data);
        
        $user = $this->getUser();
        
        try {
            $room = $this->multiplayerService->joinRoom($roomId, $user, $sanitizedData['teamName'] ?? null);
            return $this->json($room);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/room/{roomId}/leave', name: 'leave_game_room', methods: ['POST'])]
    public function leaveRoom(string $roomId): JsonResponse
    {
        $user = $this->getUser();
        
        try {
            $room = $this->multiplayerService->leaveRoom($roomId, $user);
            return $this->json($room);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/room/{roomId}/start', name: 'start_game', methods: ['POST'])]
    public function startGame(string $roomId): JsonResponse
    {
        $user = $this->getUser();
        
        try {
            $game = $this->multiplayerService->startGame($roomId, $user);
            return $this->json($game);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/room/{roomId}', name: 'get_room_status', methods: ['GET'])]
    public function getRoomStatus(string $roomId): JsonResponse
    {
        try {
            $room = $this->multiplayerService->getRoomStatus($roomId);
            return $this->json($room);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/game/{gameId}/answer', name: 'submit_multiplayer_answer', methods: ['POST'])]
    public function submitAnswer(string $gameId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $sanitizedData = $this->inputSanitizer->sanitizeMultiplayerGameData($data);
        
        $user = $this->getUser();
        
        try {
            $result = $this->multiplayerService->submitAnswer(
                $gameId,
                $user,
                $sanitizedData['questionId'],
                $sanitizedData['answer'],
                $sanitizedData['timeSpent'] ?? 0
            );
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/game/{gameId}/status', name: 'get_game_status', methods: ['GET'])]
    public function getGameStatus(string $gameId): JsonResponse
    {
        try {
            $status = $this->multiplayerService->getGameStatus($gameId);
            return $this->json($status);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/rooms/available', name: 'get_available_rooms', methods: ['GET'])]
    public function getAvailableRooms(): JsonResponse
    {
        $rooms = $this->multiplayerService->getAvailableRooms();
        return $this->json($rooms);
    }

    #[Route('/test', name: 'test_multiplayer', methods: ['GET'])]
    public function testMultiplayer(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json(['status' => 'Multiplayer API accessible', 'user' => $user ? $user->getUserIdentifier() : null]);
    }

    #[Route('/game/{gameId}/trigger-feedback', name: 'trigger_feedback', methods: ['POST'])]
    public function triggerFeedback(string $gameId): JsonResponse
    {
        try {
            $result = $this->multiplayerService->triggerFeedbackPhase($gameId);
            return $this->json(['success' => true, 'message' => 'Feedback déclenché']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/game/{gameId}/next-question', name: 'next_question', methods: ['POST'])]
    public function nextQuestion(string $gameId): JsonResponse
    {
        try {
            $result = $this->multiplayerService->triggerNextQuestion($gameId);
            return $this->json(['success' => true, 'message' => 'Question suivante déclenchée']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/invite/{roomId}', name: 'send_room_invitation', methods: ['POST'])]
    public function sendInvitation(string $roomId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $sanitizedData = $this->inputSanitizer->sanitizeMultiplayerGameData($data);
        
        $user = $this->getUser();
        
        try {
            $this->multiplayerService->sendInvitation($roomId, $user, $sanitizedData['invitedUserIds']);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }


    #[Route('/game/{gameId}/end', name: 'end_game', methods: ['POST'])]
    public function endGame(string $gameId): JsonResponse
    {
        try {
            $gameSession = $this->multiplayerService->getGameSession($gameId);
            if ($gameSession) {
                $this->multiplayerService->endGameFromClient($gameSession);
                return $this->json(['success' => true, 'message' => 'Jeu terminé']);
            }
            return $this->json(['error' => 'Jeu non trouvé'], 404);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
