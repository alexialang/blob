<?php

namespace App\Service;

use App\Entity\Room;
use App\Entity\RoomPlayer;
use App\Entity\GameSession;
use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\UserAnswer;
use App\Repository\RoomRepository;
use App\Repository\GameSessionRepository;
use App\Repository\UserAnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use App\Exception\QuizNotFoundException;
use App\Exception\GameSessionNotFoundException;
use App\Exception\GameNotStartedException;
use App\Exception\RoomNotFoundException;
use App\Exception\RoomFullException;
use App\Exception\InvalidQuestionException;
use App\Exception\AnswerAlreadySubmittedException;
use App\Exception\PlayerAlreadyInRoomException;
use App\Exception\PlayerNotInRoomException;
use App\Exception\UnauthorizedGameActionException;
use App\Exception\InsufficientPlayersException;
use Psr\Log\LoggerInterface;

class MultiplayerGameService
{
    // Variables statiques pour la gestion des réponses multijoueur
    private static array $submittedAnswers = [];
    private static array $gameAnswers = [];




    public function __construct(
        private EntityManagerInterface $entityManager,
        private HubInterface $mercureHub,
        private RoomRepository $roomRepository,
        private GameSessionRepository $gameSessionRepository,
        private UserAnswerRepository $userAnswerRepository,
        private MultiplayerTimingService $timingService,
        private MultiplayerScoreService $scoreService,
        private MultiplayerValidationService $validationService
    ) {}


    private function getUserDisplayName(User $user): string
    {
        if ($user->getPseudo()) {
            return $user->getPseudo();
        }
        
        $firstName = $user->getFirstName() ?? '';
        $lastName = $user->getLastName() ?? '';
        
        if ($firstName && $lastName) {
            return $firstName . ' ' . $lastName;
        } elseif ($firstName) {
            return $firstName;
        } elseif ($lastName) {
            return $lastName;
        }
        
        return 'Joueur ' . $user->getId();
    }

    // Génère le topic WebSocket pour une partie spécifique
    private function getGameTopic(string $gameCode): string
    {
        $cleanId = str_starts_with($gameCode, 'game_') ? substr($gameCode, 5) : $gameCode;
        return "game-{$cleanId}";
    }

    /**
     * @throws InvalidQuestionException
     * @throws QuizNotFoundException
     */
    public function createRoom(User $creator, int $quizId, int $maxPlayers = 4, bool $isTeamMode = false, ?string $roomName = null): array
    {
        $this->validationService->validateRoomData(['quizId' => $quizId, 'maxPlayers' => $maxPlayers, 'isTeamMode' => $isTeamMode, 'roomName' => $roomName]);
        
        if ($quizId <= 0) {
            throw new InvalidQuestionException($quizId, 'Quiz ID invalide');
        }

        $quiz = $this->entityManager->getRepository(Quiz::class)->find($quizId);
        if (!$quiz) {
            throw new QuizNotFoundException($quizId);
        }

        $username = $this->getUserDisplayName($creator);

        $room = new Room();
        $room->setName($roomName ?? "Room de {$username}")
             ->setQuiz($quiz)
             ->setCreator($creator)
             ->setMaxPlayers($maxPlayers)
             ->setIsTeamMode($isTeamMode)
             ->setStatus('waiting');

        $roomPlayer = new RoomPlayer();
        $roomPlayer->setRoom($room)
                   ->setUser($creator)
                   ->setIsReady(true)
                   ->setIsCreator(true)
                   ->setTeam($isTeamMode ? 'team1' : null);

        $room->addPlayer($roomPlayer);

        $this->entityManager->persist($room);
        $this->entityManager->persist($roomPlayer);
        $this->entityManager->flush();

        $roomData = $this->formatRoomData($room);
        $this->publishUpdate("room-created", $roomData);

        return $roomData;
    }

    public function joinRoom(string $roomCode, User $user, ?string $teamName = null): array
    {
        $this->validationService->validateJoinRoomData(['teamName' => $teamName]);
        
        $room = $this->roomRepository->findByRoomCode($roomCode);
        if (!$room) {
            throw new RoomNotFoundException($roomCode);
        }

        if ($room->getStatus() !== 'waiting') {
            throw new GameNotStartedException();
        }

        if ($room->getCurrentPlayerCount() >= $room->getMaxPlayers()) {
            throw new RoomFullException();
        }

        foreach ($room->getPlayers() as $player) {
            if ($player->getUser()->getId() === $user->getId()) {
                throw new PlayerAlreadyInRoomException($roomCode);
            }
        }

        $roomPlayer = new RoomPlayer();
        $roomPlayer->setRoom($room)
                   ->setUser($user)
                   ->setIsReady(false)
                   ->setIsCreator(false);

        if ($room->isTeamMode()) {
            if ($teamName && in_array($teamName, ['team1', 'team2'])) {
                $roomPlayer->setTeam($teamName);
            } else {
                $team1Count = 0;
                $team2Count = 0;
                foreach ($room->getPlayers() as $player) {
                    if ($player->getTeam() === 'team1') $team1Count++;
                    if ($player->getTeam() === 'team2') $team2Count++;
                }
                $assignedTeam = $team1Count <= $team2Count ? 'team1' : 'team2';
                $roomPlayer->setTeam($assignedTeam);
            }
        }

        $room->addPlayer($roomPlayer);
        $this->entityManager->persist($roomPlayer);
        $this->entityManager->flush();

        $roomData = $this->formatRoomData($room);

        $this->publishUpdate("room-{$roomCode}", $roomData);

        $this->publishUpdate("rooms-updated", $this->getAvailableRooms());

        return $roomData;
    }

    // Permet à un joueur de quitter un salon (gestion transfert créateur)
    public function leaveRoom(string $roomCode, User $user): array
    {
        $room = $this->roomRepository->findByRoomCode($roomCode);
        if (!$room) {
            throw new RoomNotFoundException($roomCode);
        }

        $userPlayer = null;
        foreach ($room->getPlayers() as $player) {
            if ($player->getUser()->getId() === $user->getId()) {
                $userPlayer = $player;
                break;
            }
        }

        if (!$userPlayer) {
            throw new PlayerNotInRoomException($roomCode);
        }

        $wasCreator = $userPlayer->isCreator();

        $room->removePlayer($userPlayer);
        $this->entityManager->remove($userPlayer);

        if ($wasCreator && $room->getCurrentPlayerCount() > 0) {
            $newCreator = $room->getPlayers()->first();
            $newCreator->setIsCreator(true);
            $room->setCreator($newCreator->getUser());
        }

        if ($room->getCurrentPlayerCount() === 0) {
            $this->entityManager->remove($room);
            $this->entityManager->flush();
            $this->publishUpdate("room-{$roomCode}-deleted", ['roomCode' => $roomCode]);
            return ['deleted' => true];
        }

        $this->entityManager->flush();

        $roomData = $this->formatRoomData($room);
        $this->publishUpdate("room-{$roomCode}", $roomData);
        $this->publishUpdate("rooms-updated", $this->getAvailableRooms());

        return $roomData;
    }

    public function startGame(string $roomCode, User $user): array
    {
        $room = $this->roomRepository->findByRoomCode($roomCode);
        if (!$room) {
            throw new RoomNotFoundException($roomCode);
        }

        $isCreator = false;
        foreach ($room->getPlayers() as $player) {
            if ($player->getUser()->getId() === $user->getId() && $player->isCreator()) {
                $isCreator = true;
                break;
            }
        }

        if (!$isCreator) {
            throw new UnauthorizedGameActionException('lancer le jeu');
        }

        if ($room->getCurrentPlayerCount() < 2) {
            throw new InsufficientPlayersException(2);
        }

        $gameSession = new GameSession();
        $gameSession->setRoom($room)
                    ->setStatus('playing')
                    ->setCurrentQuestionIndex(0)
                    ->setCurrentQuestionStartedAt(new \DateTimeImmutable())
                    ->setCurrentQuestionDuration(30)
                    ->setStartedAt(new \DateTimeImmutable());

        $room->setStatus('playing')
             ->setGameStartedAt(new \DateTimeImmutable())
             ->setGameSession($gameSession);

        $this->entityManager->persist($gameSession);
        $this->entityManager->flush();

        $gameData = $this->formatGameData($gameSession);

        $roomData = $this->formatRoomData($room);

        $this->publishUpdate("room-{$roomCode}", $roomData);
        $this->publishUpdate("rooms-updated", $this->getAvailableRooms());
        $this->publishUpdate("game-{$gameSession->getGameCode()}-started", $gameData);
        
        foreach ($room->getPlayers() as $player) {
            $userId = $player->getUser()->getId();
            $this->publishUpdate("user-{$userId}-game-started", [
                'action' => 'navigate_to_game',
                'gameId' => $gameSession->getGameCode(),
                'roomId' => $roomCode
            ]);
        }

        $this->publishUpdate($this->getGameTopic($gameSession->getGameCode()), [
            'action' => 'start_game',
            'currentQuestionIndex' => 0,
            'timestamp' => time()
        ]);

        // 🚀 ÉTAPE 5.11 : LANCEMENT PREMIÈRE QUESTION
        $this->startQuestion($gameSession);  // 🎯 POINT CRITIQUE - Démarre le jeu

        // 📤 ÉTAPE 5.12 : RETOUR DONNÉES AU FRONTEND
        return $gameData;  // 📤 Données formatées pour navigation
    }


    public function submitAnswer(string $gameCode, User $user, int $questionId, $answer, int $timeSpent = 0): array
    {

        
        $this->validationService->validateAnswerData(['questionId' => $questionId, 'answer' => $answer, 'timeSpent' => $timeSpent]);
        
        $gameSession = $this->gameSessionRepository->findByGameCode($gameCode);
        if (!$gameSession) {
            throw new GameSessionNotFoundException($gameCode);
        }

        if ($gameSession->getStatus() !== 'playing') {
            throw new GameNotStartedException();
        }

        $currentQuestionIndex = $gameSession->getCurrentQuestionIndex();
        $quiz = $gameSession->getRoom()->getQuiz();
        $questions = $quiz->getQuestions()->toArray();
        

        if ($currentQuestionIndex >= count($questions)) {
            throw new InvalidQuestionException($questionId, 'Index de question invalide');
        }
        
        $currentQuestion = $questions[$currentQuestionIndex];
        

        if ($questionId !== $currentQuestion->getId()) {
            throw new InvalidQuestionException($questionId, 'Question non autorisée pour cette phase du jeu');
        }

        $sessionKey = 'game_' . $gameCode . '_user_' . $user->getId() . '_question_' . $questionId;
        

        if (isset(self::$submittedAnswers[$sessionKey])) {
            throw new AnswerAlreadySubmittedException($questionId);
        }

        self::$submittedAnswers[$sessionKey] = true;

        $question = $this->entityManager->getRepository(\App\Entity\Question::class)->find($questionId);
        if (!$question) {
            throw new InvalidQuestionException($questionId, 'Question non trouvée');
        }

        $isCorrect = $this->checkAnswer($question, $answer);
        $points = $this->scoreService->calculatePoints($isCorrect, $timeSpent);

        $this->scoreService->recordAnswer($gameCode, $user, $questionId, $isCorrect, $points);
        $totalScore = $this->scoreService->calculateTotalScore($gameCode, $user);
        
        $totalQuestions = count($questions);
        $normalizedTotalScore = $this->scoreService->normalizeScoreToPercentage($totalScore, $totalQuestions);

        $leaderboard = $this->scoreService->updateLeaderboard($gameSession);

        $this->publishUpdate($this->getGameTopic($gameCode), [
            'action' => 'answer_submitted',
            'userId' => $user->getId(),
            'username' => $this->getUserDisplayName($user),
            'isCorrect' => $isCorrect,
            'points' => $points,
            'leaderboard' => $leaderboard
        ]);

        $room = $gameSession->getRoom();
        
        $allPlayersInRoom = [];
        $answeredCount = 0;
        
        foreach ($room->getPlayers() as $player) {
            $userId = $player->getUser()->getId();
            $userName = $this->getUserDisplayName($player->getUser());
            $sessionKey = 'game_' . $gameCode . '_user_' . $userId . '_question_' . $questionId;
            $hasAnswered = isset(self::$submittedAnswers[$sessionKey]);
            
            if ($hasAnswered) {
                $answeredCount++;
            }
            
            $allPlayersInRoom[] = [
                'userId' => $userId,
                'userName' => $userName,
                'hasAnswered' => $hasAnswered,
                'sessionKey' => $sessionKey
            ];
        }

        $answeredPlayersCount = $answeredCount;
        $totalPlayersCount = count($allPlayersInRoom);



        if ($answeredPlayersCount >= $totalPlayersCount) {

            $this->startFeedbackPhase($gameSession);
        } else {
            $this->publishUpdate($this->getGameTopic($gameSession->getGameCode()), [
                'action' => 'player_answered',
                'answered_count' => $answeredPlayersCount,
                'total_players' => $totalPlayersCount,
                'waiting_for_others' => true,
                'timestamp' => time()
            ]);
        }

        return [
            'isCorrect' => $isCorrect,
            'points' => $points,
            'currentScore' => $normalizedTotalScore,
            'rawCurrentScore' => $totalScore,
            'leaderboard' => $leaderboard
        ];
    }

    private function checkAnswer($question, $answer): bool
    {
        switch ($question->getTypeQuestion()->getName()) {
            case 'MCQ':
            case 'QCM':
                foreach ($question->getAnswers() as $a) {
                    if ($a->getId() === $answer && $a->isCorrect()) {
                        return true;
                    }
                }
                return false;

            case 'multiple_choice':
                $correctIds = [];
                foreach ($question->getAnswers() as $a) {
                    if ($a->isCorrect()) {
                        $correctIds[] = $a->getId();
                    }
                }
                sort($correctIds);
                sort($answer);
                return $correctIds === $answer;

            default:
                return false;
        }
    }



    public function getRoomStatus(string $roomCode): array
    {
        $room = $this->roomRepository->findByRoomCode($roomCode);
        if (!$room) {
            throw new RoomNotFoundException($roomCode);
        }

        return $this->formatRoomData($room);
    }

    public function getGameStatus(string $gameCode): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('gs')
           ->from('App\Entity\GameSession', 'gs')
           ->where('gs.gameCode = :gameCode')
           ->setParameter('gameCode', $gameCode);
        
        $gameSession = $qb->getQuery()->getSingleResult();
        
        if (!$gameSession) {
            throw new GameSessionNotFoundException($gameCode);
        }



        $this->timingService->ensureTimingExists($gameSession);

        $gameData = $this->formatGameData($gameSession);
        

        
        return $gameData;
    }

    public function getAvailableRooms(): array
    {
        $rooms = $this->roomRepository->findAvailableRooms();
        return array_map([$this, 'formatRoomData'], $rooms);
    }

    public function sendInvitation(string $roomCode, User $sender, array $invitedUserIds): void
    {
        $this->validationService->validateInvitationData(['invitedUserIds' => $invitedUserIds]);
        
        $room = $this->roomRepository->findByRoomCode($roomCode);
        if (!$room) {
            throw new RoomNotFoundException($roomCode);
        }

        foreach ($invitedUserIds as $userId) {
            $topic = "user-{$userId}-invitation";

            $invitationData = [
                'roomId' => $roomCode,
                'roomName' => $room->getName(),
                'senderName' => $this->getUserDisplayName($sender),
                'quiz' => [
                    'id' => $room->getQuiz()->getId(),
                    'title' => $room->getQuiz()->getTitle(),
                    'questionCount' => $room->getQuiz()->getQuestionCount()
                ],
                'currentPlayers' => $room->getCurrentPlayerCount(),
                'maxPlayers' => $room->getMaxPlayers()
            ];

            $this->publishUpdate($topic, $invitationData);
        }
    }

    private function formatRoomData(Room $room): array
    {
        $players = [];
        foreach ($room->getPlayers() as $player) {
            $players[] = [
                'id' => $player->getUser()->getId(),
                'username' => $this->getUserDisplayName($player->getUser()),
                'isReady' => $player->isReady(),
                'isCreator' => $player->isCreator(),
                'team' => $player->getTeam()
            ];
        }

        $teams = null;
        if ($room->isTeamMode()) {
            $teams = ['team1' => [], 'team2' => []];
            foreach ($room->getPlayers() as $player) {
                if ($player->getTeam()) {
                    $teams[$player->getTeam()][] = $player->getUser()->getId();
                }
            }
        }

        return [
            'id' => $room->getRoomCode(),
            'name' => $room->getName(),
            'quiz' => [
                'id' => $room->getQuiz()->getId(),
                'title' => $room->getQuiz()->getTitle(),
                'questionCount' => $room->getQuiz()->getQuestionCount()
            ],
            'creator' => [
                'id' => $room->getCreator()->getId(),
                'username' => $this->getUserDisplayName($room->getCreator())
            ],
            'maxPlayers' => $room->getMaxPlayers(),
            'isTeamMode' => $room->isTeamMode(),
            'status' => $room->getStatus(),
            'players' => $players,
            'teams' => $teams,
            'createdAt' => $room->getCreatedAt()->getTimestamp(),
            'gameStartedAt' => $room->getGameStartedAt()?->getTimestamp(),
            'gameId' => $room->getGameSession()?->getGameCode()
        ];
    }

    private function formatGameData(GameSession $gameSession): array
    {
        $room = $gameSession->getRoom();
        $gameCode = $gameSession->getGameCode();
        $sharedScores = $gameSession->getSharedScores() ?? [];

        $playerScores = [];
        foreach ($room->getPlayers() as $roomPlayer) {
            $user = $roomPlayer->getUser();
            $userId = $user->getId();
            $username = $this->getUserDisplayName($user);

            if (isset($sharedScores[$username])) {
                $playerScores[$userId] = $sharedScores[$username];
            } else {
                $totalScore = 0;
                foreach (self::$gameAnswers as $key => $answer) {
                    if (strpos($key, 'game_' . $gameCode . '_user_' . $userId) === 0) {
                        $totalScore += $answer['points'];
                    }
                }
                $playerScores[$userId] = $totalScore;
            }
        }

        $timeLeft = $this->timingService->calculateTimeLeft($gameSession);
        $questionStartTime = $gameSession->getCurrentQuestionStartedAt()?->getTimestamp();
        $questionDuration = $gameSession->getCurrentQuestionDuration();
        
        return [
            'id' => $gameSession->getGameCode(),
            'roomId' => $room->getRoomCode(),
            'quiz' => [
                'id' => $room->getQuiz()->getId(),
                'title' => $room->getQuiz()->getTitle(),
                'questionCount' => $room->getQuiz()->getQuestionCount()
            ],
            'players' => array_map(function($player) {
                return [
                    'id' => $player->getUser()->getId(),
                    'username' => $this->getUserDisplayName($player->getUser()),
                    'team' => $player->getTeam()
                ];
            }, $room->getPlayers()->toArray()),
            'isTeamMode' => $room->isTeamMode(),
            'status' => $gameSession->getStatus(),
            'currentQuestionIndex' => $gameSession->getCurrentQuestionIndex(),
            'startedAt' => $gameSession->getStartedAt()->getTimestamp(),
            'leaderboard' => $this->scoreService->updateLeaderboard($gameSession),
            'playerScores' => $playerScores,
            'sharedScores' => $sharedScores,
            'timeLeft' => $timeLeft,
            'questionStartTime' => $questionStartTime,
            'questionDuration' => $questionDuration
        ];
    }

    private function publishUpdate(string $topic, array $data): void
    {
        try {
            $update = new Update(
                $topic,
                json_encode($data)
            );

            $this->mercureHub->publish($update);
        } catch (\Exception $e) {

        }
    }


    private function startQuestion(GameSession $gameSession): void
    {
        $quiz = $gameSession->getRoom()->getQuiz();
        $questionIndex = $gameSession->getCurrentQuestionIndex();

        $questions = $quiz->getQuestions()->toArray();

        if ($questionIndex >= count($questions)) {
            $this->endGame($gameSession);
            return;
        }

        $question = $questions[$questionIndex];

        $this->timingService->setupQuestionTiming($gameSession, 30);

        $questionData = [
            'questionIndex' => $questionIndex,
            'question' => [
                'id' => $question->getId(),
                'question' => $question->getQuestion(),
                'type_question' => $question->getTypeQuestion()->getName(),
                'answers' => array_map(function($answer) {
                    return [
                        'id' => $answer->getId(),
                        'answer' => $answer->getAnswer(),
                        'pair_id' => $answer->getPairId(),
                        'order_correct' => $answer->getOrderCorrect()
                    ];
                }, $question->getAnswers()->toArray())
            ],
            'timeLeft' => 30,
            'questionStartTime' => $gameSession->getCurrentQuestionStartedAt()->getTimestamp(),
            'questionDuration' => $gameSession->getCurrentQuestionDuration()
        ];

        $gameCode = $gameSession->getGameCode();

        $this->publishUpdate($this->getGameTopic($gameCode), $questionData);
        
        $this->publishUpdate($this->getGameTopic($gameCode), [
            'action' => 'start_timer',
            'duration' => 30,
            'timestamp' => time()
        ]);
    }
    public function triggerFeedbackPhase(string $gameCode): void
    {
        $gameSession = $this->gameSessionRepository->findByGameCode($gameCode);
        if (!$gameSession) {
            return;
        }

        $this->startFeedbackPhase($gameSession);
    }

    private function startFeedbackPhase(GameSession $gameSession): void
    {
        $gameCode = $gameSession->getGameCode();
        $quiz = $gameSession->getRoom()->getQuiz();
        $questionIndex = $gameSession->getCurrentQuestionIndex();
        $questions = $quiz->getQuestions()->toArray();
        
        if ($questionIndex >= count($questions)) {
            return;
        }
        
        $currentQuestion = $questions[$questionIndex];

        $correctAnswers = [];
        foreach ($currentQuestion->getAnswers() as $answer) {
            if ($answer->isCorrect()) {
                $correctAnswers[] = [
                    'id' => $answer->getId(),
                    'answer' => $answer->getAnswer()
                ];
            }
        }

        $leaderboard = $this->scoreService->updateLeaderboard($gameSession);
        
        $this->publishUpdate($this->getGameTopic($gameCode), [
            'action' => 'show_feedback',
            'correctAnswers' => $correctAnswers,
            'leaderboard' => $leaderboard,
            'timestamp' => time()
        ]);

        if ($questionIndex + 1 >= count($questions)) {
            $this->endGameInternal($gameSession);
        } else {

        }
    }

    public function triggerNextQuestion(string $gameCode): void
    {

        
        $gameSession = $this->gameSessionRepository->findByGameCode($gameCode);
        if (!$gameSession) {
            return;
        }

        if (!$this->timingService->checkTransitionCooldown($gameSession, 3)) {
            return;
        }

        $questionIndex = $gameSession->getCurrentQuestionIndex();
        $quiz = $gameSession->getRoom()->getQuiz();
        $questions = $quiz->getQuestions()->toArray();




        if ($questionIndex + 1 >= count($questions)) {
            $this->endGameInternal($gameSession);
            return;
        }

        $newIndex = $questionIndex + 1;
        
        $gameSession->setCurrentQuestionIndex($newIndex);
        $this->timingService->setupQuestionTiming($gameSession, 30);
        
        $updatedIndex = $gameSession->getCurrentQuestionIndex();
        
        $question = $questions[$newIndex];
        
        $questionData = [
            'questionIndex' => $newIndex,
            'question' => [
                'id' => $question->getId(),
                'question' => $question->getQuestion(),
                'type_question' => $question->getTypeQuestion()->getName(),
                'answers' => array_map(function($answer) {
                    return [
                        'id' => $answer->getId(),
                        'answer' => $answer->getAnswer(),
                        'pair_id' => $answer->getPairId(),
                        'order_correct' => $answer->getOrderCorrect()
                    ];
                }, $question->getAnswers()->toArray())
            ],
            'timeLeft' => 30,
            'timestamp' => time()
        ];

        $gameCode = $gameSession->getGameCode();
        

        
        $this->publishUpdate($this->getGameTopic($gameCode), [
            'action' => 'new_question',
            'questionIndex' => $newIndex,
            'question' => $questionData['question'],
            'timeLeft' => 30,
            'questionStartTime' => $gameSession->getCurrentQuestionStartedAt()->getTimestamp(),
            'questionDuration' => $gameSession->getCurrentQuestionDuration(),
            'timestamp' => time()
        ]);
    }

    public function endGame(string $gameId, $user): array
    {
        try {
            $gameSession = $this->gameSessionRepository->findByGameCode($gameId);
            if (!$gameSession) {
                throw new GameSessionNotFoundException('unknown');
            }

            $room = $gameSession->getRoom();
            $isPlayer = $room->getPlayers()->exists(function($key, $player) use ($user) {
                return $player->getUser()->getId() === $user->getId();
            });

            if (!$isPlayer) {
                throw new PlayerNotInRoomException('unknown');
            }

            $this->endGameInternal($gameSession);

            return [
                'success' => true,
                'message' => 'Partie terminée avec succès',
                'gameId' => $gameId
            ];
        } catch (\Exception $e) {

            throw $e;
        }
    }


    public function endGameInternal(GameSession $gameSession): void
    {
        $gameCode = $gameSession->getGameCode();
        
        $gameSession->setStatus('finished');
        $gameSession->setFinishedAt(new \DateTimeImmutable());
        
        $room = $gameSession->getRoom();
        $room->setStatus('finished');
        
        $this->saveMultiplayerGameResults($gameSession);
        
        $this->entityManager->persist($gameSession);
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        $finalLeaderboard = $this->scoreService->updateLeaderboard($gameSession);

        $this->publishUpdate($this->getGameTopic($gameCode), [
            'action' => 'game_finished',
            'leaderboard' => $finalLeaderboard
        ]);
    }


    public function submitPlayerScores(string $gameId, array $playerScores): array
    {
        try {
            $gameSession = $this->gameSessionRepository->findByGameCode($gameId);
            if (!$gameSession) {
                throw new GameSessionNotFoundException('unknown');
            }

            $existingScores = $gameSession->getSharedScores() ?? [];
            $mergedScores = $existingScores;

            foreach ($playerScores as $username => $newScore) {
                $oldScore = $existingScores[$username] ?? 0;
                
                if ($newScore >= $oldScore) {
                    $mergedScores[$username] = $newScore;
                } else {
                }
            }

            $gameSession->setSharedScores($mergedScores);
            $this->entityManager->flush();


            return [
                'success' => true,
                'message' => 'Scores partagés avec succès',
                'sharedScores' => $mergedScores
            ];
        } catch (\Exception $e) {

            throw $e;
        }
    }


    private function saveMultiplayerGameResults(GameSession $gameSession): void
    {
        try {
            $gameCode = $gameSession->getGameCode();
            $quiz = $gameSession->getRoom()->getQuiz();
            $totalQuestions = $quiz->getQuestions()->count();
            
            $sharedScores = $gameSession->getSharedScores() ?? [];
            

            
            foreach ($gameSession->getRoom()->getPlayers() as $roomPlayer) {
                $user = $roomPlayer->getUser();
                $username = $this->getUserDisplayName($user);
                
                $rawTotalScore = $sharedScores[$username] ?? 0;
                
                $normalizedScore = $this->scoreService->normalizeScoreToPercentage($rawTotalScore, $totalQuestions);
                
                $userAnswer = new UserAnswer();
                $userAnswer->setUser($user);
                $userAnswer->setQuiz($quiz);
                $userAnswer->setTotalScore($normalizedScore);
                $userAnswer->setDateAttempt(new \DateTime());

                $this->entityManager->persist($userAnswer);
                
            }
            
            $this->entityManager->flush();
            
        } catch (\Exception $e) {


            throw $e;
        }
    }

    public function handleTimeExpired(string $gameCode): void
    {
        $gameSession = $this->gameSessionRepository->findByGameCode($gameCode);
        if (!$gameSession) {
            throw new GameSessionNotFoundException($gameCode);
        }

        $room = $gameSession->getRoom();
        $currentQuestionIndex = $gameSession->getCurrentQuestionIndex();
        $quiz = $gameSession->getRoom()->getQuiz();
        $questions = $quiz->getQuestions()->toArray();
        
        if ($currentQuestionIndex >= count($questions)) {
            return;
        }

        $currentQuestion = $questions[$currentQuestionIndex];
        
        $answeredUserIds = [];
        foreach (self::$submittedAnswers as $key => $value) {
            if (strpos($key, 'game_' . $gameCode . '_user_') === 0 && strpos($key, '_question_' . $currentQuestion->getId()) !== false) {
                $parts = explode('_', $key);
                $userId = $parts[3] ?? null;
                if ($userId) {
                    $answeredUserIds[] = (int)$userId;
                }
            }
        }

        foreach ($room->getPlayers() as $roomPlayer) {
            $userId = $roomPlayer->getUser()->getId();
            
            if (!in_array($userId, $answeredUserIds)) {
                $sessionKey = 'game_' . $gameCode . '_user_' . $userId . '_question_' . $currentQuestion->getId();
                self::$submittedAnswers[$sessionKey] = true;
                
                $answerKey = 'game_' . $gameCode . '_user_' . $userId . '_q_' . $currentQuestion->getId();
                self::$gameAnswers[$answerKey] = [
                    'userId' => $userId,
                    'questionId' => $currentQuestion->getId(),
                    'isCorrect' => false,
                    'points' => 0,
                    'timestamp' => time()
                ];
                
            }
        }

        $this->startFeedbackPhase($gameSession);
        
        $leaderboard = $this->scoreService->updateLeaderboard($gameSession);
        $this->publishUpdate($this->getGameTopic($gameCode), [
            'action' => 'time_expired',
            'leaderboard' => $leaderboard,
            'timestamp' => time()
        ]);
        
    }
}
