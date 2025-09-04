<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MultiplayerGameService;
use App\Service\GroupService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Annotations as OA;

#[Route('/api/multiplayer')]
#[IsGranted('ROLE_USER')]
class MultiplayerGameController extends AbstractSecureController
{
    public function __construct(
        private MultiplayerGameService $multiplayerService,
        private GroupService $groupService,
        private UserService $userService
        ) {
    }

    /**
     * @OA\Post(summary="Créer un nouveau salon multijoueur", tags={"Multiplayer"})
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="quizId", type="integer"),
     *         @OA\Property(property="maxPlayers", type="integer"),
     *         @OA\Property(property="isTeamMode", type="boolean"),
     *         @OA\Property(property="roomName", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Salon créé avec succès")
     * @OA\Security(name="bearerAuth")
     */

    #[Route('/room/create', name: 'create_game_room', methods: ['POST'])]
    public function createRoom(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $user = $this->getCurrentUser();
            
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non connecté'], 401);
            }
            
            $quizId = $data['quizId'] ?? 0;
            $maxPlayers = $data['maxPlayers'] ?? 4;
            $isTeamMode = $data['isTeamMode'] ?? false;
            $roomName = $data['roomName'] ?? null;
            
            $room = $this->multiplayerService->createRoom(
                $user,
                $quizId,
                $maxPlayers,
                $isTeamMode,
                $roomName
            );

            return $this->json($room);
        } catch (ValidationFailedException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                $errorMessages[] = $violation->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(summary="Rejoindre un salon multijoueur", tags={"Multiplayer"})
     * @OA\Parameter(name="roomId", in="path", required=true, @OA\Schema(type="string"))
     * @OA\RequestBody(
     *     required=false,
     *     @OA\JsonContent(
     *         @OA\Property(property="teamName", type="string")
     *     )
     * )
     * @OA\Response(response=200, description="Salon rejoint avec succès")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/room/{roomId}/join', name: 'join_game_room', methods: ['POST'])]
    public function joinRoom(string $roomId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $user = $this->getCurrentUser();
        
            $room = $this->multiplayerService->joinRoom($roomId, $user, $data['teamName'] ?? null);
            return $this->json($room);
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

    /**
     * @OA\Post(summary="Quitter un salon multijoueur", tags={"Multiplayer"})
     * @OA\Parameter(name="roomId", in="path", required=true, @OA\Schema(type="string"))
     * @OA\Response(response=200, description="Salon quitté avec succès")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/room/{roomId}/leave', name: 'leave_game_room', methods: ['POST'])]
    public function leaveRoom(string $roomId): JsonResponse
    {
        $user = $this->getCurrentUser();
        
        try {
            $room = $this->multiplayerService->leaveRoom($roomId, $user);
            return $this->json($room);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(summary="Démarrer une partie multijoueur", tags={"Multiplayer"})
     * @OA\Parameter(name="roomId", in="path", required=true, @OA\Schema(type="string"))
     * @OA\Response(response=200, description="Partie démarrée avec succès")
     * @OA\Security(name="bearerAuth")
     */

    #[Route('/room/{roomId}/start', name: 'start_game', methods: ['POST'])]
    public function startGame(string $roomId): JsonResponse
    {
        $user = $this->getCurrentUser();
        
        try {
            $game = $this->multiplayerService->startGame($roomId, $user);
            return $this->json($game);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(summary="Consulter l'état d'un salon multijoueur", tags={"Multiplayer"})
     * @OA\Parameter(name="roomId", in="path", required=true, @OA\Schema(type="string"))
     * @OA\Response(response=200, description="État du salon")
     * @OA\Security(name="bearerAuth")
     */
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


    /**
     * @OA\Post(summary="Soumettre une réponse en multijoueur", tags={"Multiplayer"})
     * @OA\Parameter(name="gameId", in="path", required=true, @OA\Schema(type="string"))
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="questionId", type="integer"),
     *         @OA\Property(property="answer", type="mixed"),
     *         @OA\Property(property="timeSpent", type="integer")
     *     )
     * )
     * @OA\Response(response=200, description="Réponse soumise avec succès")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/game/{gameId}/answer', name: 'submit_multiplayer_answer', methods: ['POST'])]
    public function submitAnswer(string $gameId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            

            $user = $this->getCurrentUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non connecté'], 401);
            }
        
            $result = $this->multiplayerService->submitAnswer(
                $gameId,
                $user,
                $data['questionId'],
                $data['answer'],
                $data['timeSpent'] ?? 0
            );
            return $this->json($result);
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


    #[Route('/game/{gameId}/scores', name: 'submit_player_scores', methods: ['POST'])]
    public function submitPlayerScores(string $gameId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            

            $user = $this->getCurrentUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non connecté'], 401);
            }
        
            $result = $this->multiplayerService->submitPlayerScores(
                $gameId,
                $data['playerScores'] ?? []
            );
            return $this->json($result);
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

    /**
     * @OA\Get(summary="Lister les salons multijoueur disponibles", tags={"Multiplayer"})
     * @OA\Response(response=200, description="Liste des salons disponibles")
     * @OA\Security(name="bearerAuth")
     */

    #[Route('/rooms/available', name: 'get_available_rooms', methods: ['GET'])]
    public function getAvailableRooms(): JsonResponse
    {
        $rooms = $this->multiplayerService->getAvailableRooms();
        return $this->json($rooms);
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
        try {
            $data = json_decode($request->getContent(), true);
            
            $user = $this->getCurrentUser();
        
            $this->multiplayerService->sendInvitation($roomId, $user, $data['invitedUserIds']);
            return $this->json(['success' => true]);
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

    #[Route('/game/{gameId}/end', name: 'end_game', methods: ['POST'])]
    public function endGame(string $gameId): JsonResponse
    {
        $user = $this->getCurrentUser();
        
        try {
            $game = $this->multiplayerService->endGame($gameId, $user);
            return $this->json($game);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(summary="Lister tous les utilisateurs pour invitation multijoueur", tags={"Multiplayer"})
     * @OA\Response(response=200, description="Liste de tous les utilisateurs")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/users/available', name: 'get_available_users', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getAvailableUsers(): JsonResponse
    {

        $users = $this->userService->getActiveUsersForMultiplayer();
        
        return $this->json($users, 200, [], ['groups' => ['user:public']]);
    }

    /**
     * @OA\Get(summary="Lister les groupes de son entreprise pour invitation multijoueur", tags={"Multiplayer"})
     * @OA\Response(response=200, description="Liste des groupes de son entreprise")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/groups/company', name: 'get_company_groups', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCompanyGroups(): JsonResponse
    {
        $user = $this->getCurrentUser();
        $userCompany = $user->getCompany();
        
        if (!$userCompany) {
            return $this->json(['error' => 'Vous n\'appartenez à aucune entreprise'], 403);
        }
        
        $groups = $this->groupService->getGroupsByCompany($userCompany);
        
        return $this->json($groups, 200, [], ['groups' => ['group:read']]);
    }

    /**
     * @OA\Get(summary="Lister les membres de son entreprise pour invitation multijoueur", tags={"Multiplayer"})
     * @OA\Response(response=200, description="Liste des membres de son entreprise")
     * @OA\Security(name="bearerAuth")
     */
    #[Route('/members/company', name: 'get_company_members', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCompanyMembers(): JsonResponse
    {
        $user = $this->getCurrentUser();
        $userCompany = $user->getCompany();
        
        if (!$userCompany) {
            return $this->json(['error' => 'Vous n\'appartenez à aucune entreprise'], 403);
        }
        
        $members = $this->userService->getUsersByCompany($userCompany);
        
        return $this->json($members, 200, [], ['groups' => ['user:public']]);
    }
}