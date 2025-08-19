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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class MultiplayerGameService
{
    private static array $submittedAnswers = [];
    private static array $gameAnswers = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HubInterface $mercureHub,
        private RoomRepository $roomRepository,
        private GameSessionRepository $gameSessionRepository,
        private UserAnswerRepository $userAnswerRepository,
        private ValidatorInterface $validator
    ) {}

    private function getUserDisplayName(User $user): string
    {
        return $user->getPseudo() ?? ($user->getFirstName() . ' ' . $user->getLastName());
    }

    private function getGameTopic(string $gameCode): string
    {
        $cleanId = str_starts_with($gameCode, 'game_') ? substr($gameCode, 5) : $gameCode;
        return "game-{$cleanId}";
    }

    public function createRoom(User $creator, int $quizId, int $maxPlayers = 4, bool $isTeamMode = false, ?string $roomName = null): array
    {
        $this->validateRoomData(['quizId' => $quizId, 'maxPlayers' => $maxPlayers, 'isTeamMode' => $isTeamMode, 'roomName' => $roomName]);
        
        if ($quizId <= 0) {
            throw new \Exception('Quiz ID invalide');
        }

        $quiz = $this->entityManager->getRepository(Quiz::class)->find($quizId);
        if (!$quiz) {
            throw new \Exception("Quiz avec ID $quizId non trouvé");
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
        $this->validateJoinRoomData(['teamName' => $teamName]);
        
        $room = $this->roomRepository->findByRoomCode($roomCode);
        if (!$room) {
            throw new \Exception('Salon non trouvé');
        }

        if ($room->getStatus() !== 'waiting') {
            throw new \Exception('Le jeu a déjà commencé');
        }

        if ($room->getCurrentPlayerCount() >= $room->getMaxPlayers()) {
            throw new \Exception('Salon complet');
        }

        foreach ($room->getPlayers() as $player) {
            if ($player->getUser()->getId() === $user->getId()) {
                throw new \Exception('Vous êtes déjà dans ce salon');
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

    public function leaveRoom(string $roomCode, User $user): array
    {
        $room = $this->roomRepository->findByRoomCode($roomCode);
        if (!$room) {
            throw new \Exception('Salon non trouvé');
        }

        $userPlayer = null;
        foreach ($room->getPlayers() as $player) {
            if ($player->getUser()->getId() === $user->getId()) {
                $userPlayer = $player;
                break;
            }
        }

        if (!$userPlayer) {
            throw new \Exception('Vous n\'êtes pas dans ce salon');
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
            throw new \Exception('Salon non trouvé');
        }

        $isCreator = false;
        foreach ($room->getPlayers() as $player) {
            if ($player->getUser()->getId() === $user->getId() && $player->isCreator()) {
                $isCreator = true;
                break;
            }
        }

        if (!$isCreator) {
            throw new \Exception('Seul le créateur peut lancer le jeu');
        }

        if ($room->getCurrentPlayerCount() < 2) {
            throw new \Exception('Il faut au moins 2 joueurs pour commencer');
        }

        $gameSession = new GameSession();
        $gameSession->setRoom($room)
                    ->setStatus('playing')
                    ->setCurrentQuestionIndex(0)
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

        $this->startQuestion($gameSession);

        return $gameData;
    }

    public function submitAnswer(string $gameCode, User $user, int $questionId, $answer, int $timeSpent = 0): array
    {
        $this->validateAnswerData(['questionId' => $questionId, 'answer' => $answer, 'timeSpent' => $timeSpent]);
        
        $gameSession = $this->gameSessionRepository->findByGameCode($gameCode);
        if (!$gameSession) {
            throw new \Exception('Jeu non trouvé');
        }

        if ($gameSession->getStatus() !== 'playing') {
            throw new \Exception('Le jeu n\'est pas en cours');
        }

        $currentQuestionIndex = $gameSession->getCurrentQuestionIndex();
        $quiz = $gameSession->getRoom()->getQuiz();
        $questions = $quiz->getQuestions()->toArray();
        
        if ($currentQuestionIndex >= count($questions)) {
            throw new \Exception('Question invalide');
        }
        
        $currentQuestion = $questions[$currentQuestionIndex];
        
        $allowedQuestionIds = [$currentQuestion->getId()];
        
        for ($i = 1; $i <= 2; $i++) {
            if ($currentQuestionIndex - $i >= 0) {
                $previousQuestion = $questions[$currentQuestionIndex - $i];
                $allowedQuestionIds[] = $previousQuestion->getId();
            }
        }
        
        if ($currentQuestionIndex + 1 < count($questions)) {
            $nextQuestion = $questions[$currentQuestionIndex + 1];
            $allowedQuestionIds[] = $nextQuestion->getId();
        }
        
        if (!in_array($questionId, $allowedQuestionIds)) {
        }

        $sessionKey = 'game_' . $gameCode . '_user_' . $user->getId() . '_question_' . $questionId;
        
        if (isset(self::$submittedAnswers[$sessionKey])) {
            throw new \Exception('Réponse déjà soumise pour cette question');
        }

        self::$submittedAnswers[$sessionKey] = true;

        $question = $this->entityManager->getRepository(\App\Entity\Question::class)->find($questionId);
        if (!$question) {
            throw new \Exception('Question non trouvée');
        }

        $isCorrect = $this->checkAnswer($question, $answer);
        $points = $isCorrect ? max(10 - floor($timeSpent / 3), 1) : 0;


        $answerKey = 'game_' . $gameCode . '_user_' . $user->getId() . '_q_' . $questionId;
        self::$gameAnswers[$answerKey] = [
            'userId' => $user->getId(),
            'questionId' => $questionId,
            'isCorrect' => $isCorrect,
            'points' => $points,
            'timestamp' => time()
        ];

        $totalScore = 0;
        foreach (self::$gameAnswers as $key => $answerData) {
            if (strpos($key, 'game_' . $gameCode . '_user_' . $user->getId()) === 0) {
                $totalScore += $answerData['points'];
            }
        }
        
        $totalQuestions = count($questions);
        $normalizedTotalScore = $totalQuestions > 0 ? round($totalScore / $totalQuestions) : 0;

        $leaderboard = $this->updateLeaderboard($gameSession);

        $this->publishUpdate($this->getGameTopic($gameCode), [
            'action' => 'answer_submitted',
            'userId' => $user->getId(),
            'username' => $this->getUserDisplayName($user),
            'isCorrect' => $isCorrect,
            'points' => $points,
            'leaderboard' => $leaderboard
        ]);

        $room = $gameSession->getRoom();
        $totalPlayers = $room->getCurrentPlayerCount();
        $answeredCount = 0;
        
        foreach ($room->getPlayers() as $player) {
            $userId = $player->getUser()->getId();
            $checkKey = 'game_' . $gameCode . '_user_' . $userId . '_question_' . $questionId;
            if (isset(self::$submittedAnswers[$checkKey])) {
                $answeredCount++;
            }
        }
        
        $allPlayersInRoom = [];
        foreach ($room->getPlayers() as $player) {
            $userId = $player->getUser()->getId();
            $userName = $this->getUserDisplayName($player->getUser());
            $sessionKey = 'game_' . $gameCode . '_user_' . $userId . '_question_' . $questionId;
            $hasAnswered = isset(self::$submittedAnswers[$sessionKey]);
            
            $allPlayersInRoom[] = [
                'userId' => $userId,
                'userName' => $userName,
                'hasAnswered' => $hasAnswered,
                'sessionKey' => $sessionKey
            ];
        }
        
        $answeredPlayersCount = count(array_filter($allPlayersInRoom, fn($p) => $p['hasAnswered']));
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

    private function updateLeaderboard(GameSession $gameSession): array
    {
        $gameCode = $gameSession->getGameCode();
        $leaderboard = [];

        foreach ($gameSession->getRoom()->getPlayers() as $roomPlayer) {
            $user = $roomPlayer->getUser();
            $userId = $user->getId();

            $totalScore = 0;
            foreach (self::$gameAnswers as $key => $answer) {
                if (strpos($key, 'game_' . $gameCode . '_user_' . $userId) === 0) {
                    $totalScore += $answer['points'];
                }
            }

            $leaderboard[] = [
                'userId' => $userId,
                'username' => $this->getUserDisplayName($user),
                'score' => $totalScore,
                'team' => $roomPlayer->getTeam()
            ];
        }

        usort($leaderboard, fn($a, $b) => $b['score'] - $a['score']);

        foreach ($leaderboard as $index => &$entry) {
            $entry['position'] = $index + 1;
        }

        return $leaderboard;
    }

    public function getRoomStatus(string $roomCode): array
    {
        $room = $this->roomRepository->findByRoomCode($roomCode);
        if (!$room) {
            throw new \Exception("Salon '$roomCode' non trouvé");
        }

        return $this->formatRoomData($room);
    }

    public function getGameStatus(string $gameCode): array
    {
        $gameSession = $this->gameSessionRepository->findByGameCode($gameCode);
        if (!$gameSession) {
            throw new \Exception('Jeu non trouvé');
        }

        return $this->formatGameData($gameSession);
    }

    public function getAvailableRooms(): array
    {
        $rooms = $this->roomRepository->findAvailableRooms();
        return array_map([$this, 'formatRoomData'], $rooms);
    }

    public function sendInvitation(string $roomCode, User $sender, array $invitedUserIds): void
    {
        $this->validateInvitationData(['invitedUserIds' => $invitedUserIds]);
        
        $room = $this->roomRepository->findByRoomCode($roomCode);
        if (!$room) {
            throw new \Exception('Salon non trouvé');
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
            'leaderboard' => $this->updateLeaderboard($gameSession)
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
            // Log l'erreur pour debug
            error_log("Erreur Mercure pour le topic {$topic}: " . $e->getMessage());
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
            'timeLeft' => 30
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

        $leaderboard = $this->updateLeaderboard($gameSession);
        
        $this->publishUpdate($this->getGameTopic($gameCode), [
            'action' => 'show_feedback',
            'correctAnswers' => $correctAnswers,
            'leaderboard' => $leaderboard,
            'timestamp' => time()
        ]);

        if ($questionIndex + 1 >= count($questions)) {
            $this->endGame($gameSession);
        } else {
            $newIndex = $questionIndex + 1;
            
            $gameSession->setCurrentQuestionIndex($newIndex);
            $this->entityManager->persist($gameSession);
            $this->entityManager->flush();
            
            $this->triggerNextQuestion($gameCode);
        }
    }

    public function triggerNextQuestion(string $gameCode): void
    {
        $gameSession = $this->gameSessionRepository->findByGameCode($gameCode);
        if (!$gameSession) {
            return;
        }

        $questionIndex = $gameSession->getCurrentQuestionIndex();
        $quiz = $gameSession->getRoom()->getQuiz();
        $questions = $quiz->getQuestions()->toArray();

        if ($questionIndex + 1 >= count($questions)) {
            $this->endGame($gameSession);
            return;
        }

        $newIndex = $questionIndex + 1;
        $gameSession->setCurrentQuestionIndex($newIndex);
        $this->entityManager->flush();
        
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
            'timestamp' => time()
        ]);
    }

    private function endGame(GameSession $gameSession): void
    {
        $gameCode = $gameSession->getGameCode();
        
        $gameSession->setStatus('finished');
        $gameSession->setFinishedAt(new \DateTimeImmutable());
        
        $room = $gameSession->getRoom();
        $room->setStatus('finished');
        
        $this->entityManager->persist($gameSession);
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        $finalLeaderboard = $this->updateLeaderboard($gameSession);

        $this->publishUpdate($this->getGameTopic($gameCode), [
            'action' => 'game_finished',
            'leaderboard' => $finalLeaderboard
        ]);
    }



    private function checkAllPlayersAnswered(GameSession $gameSession, bool $forceTimeout = false): bool
    {
        $room = $gameSession->getRoom();
        $gameCode = $gameSession->getGameCode();
        $totalPlayers = $room->getCurrentPlayerCount();
        $currentQuestionIndex = $gameSession->getCurrentQuestionIndex();
        
        $quiz = $room->getQuiz();
        $questions = $quiz->getQuestions()->toArray();
        if ($currentQuestionIndex >= count($questions)) {
            return true;
        }
        $currentQuestion = $questions[$currentQuestionIndex];
        $questionId = $currentQuestion->getId();

        $answeredCount = 0;
        $answeredPlayers = [];
        $allPlayers = [];
        
        foreach ($room->getPlayers() as $player) {
            $userId = $player->getUser()->getId();
            $userName = $this->getUserDisplayName($player->getUser());
            $allPlayers[] = "{$userName}({$userId})";
            
            $sessionKey = 'game_' . $gameCode . '_user_' . $userId . '_question_' . $questionId;
            if (isset(self::$submittedAnswers[$sessionKey])) {
                $answeredCount++;
                $answeredPlayers[] = "{$userName}({$userId})";
            }
        }
        
        if ($answeredCount >= $totalPlayers || $forceTimeout) {

            $quiz = $room->getQuiz();
            $questions = $quiz->getQuestions();
            $currentQuestionObj = $questions[$currentQuestion] ?? null;

            if ($currentQuestionObj) {
                $correctAnswers = array_filter($currentQuestionObj->getAnswers()->toArray(),
                    fn($answer) => $answer->isCorrect()
                );

                $correctAnswersData = array_map(function($answer) {
                    return [
                        'id' => $answer->getId(),
                        'answer' => $answer->getAnswer()
                    ];
                }, $correctAnswers);

                $this->publishUpdate($this->getGameTopic($gameCode), [
                    'action' => 'show_feedback',
                    'correctAnswers' => $correctAnswersData,
                    'leaderboard' => $this->updateLeaderboard($gameSession),
                    'timestamp' => time()
                ]);

                $quiz = $gameSession->getRoom()->getQuiz();
                $totalQuestions = count($quiz->getQuestions()->toArray());
                if ($currentQuestionIndex >= $totalQuestions - 1) {
                }
                
                return true;
            }
        }
        
        return $answeredCount >= $totalPlayers;
    }



    public function getGameSession(string $gameCode): ?GameSession
    {
        return $this->gameSessionRepository->findByGameCode($gameCode);
    }

    public function endGameFromClient(GameSession $gameSession): void
    {
        $this->endGame($gameSession);
    }

    private function validateRoomData(array $data): void
    {
        $constraints = new Assert\Collection([
            'quizId' => [
                new Assert\NotBlank(['message' => 'L\'ID du quiz est requis']),
                new Assert\Type(['type' => 'integer', 'message' => 'L\'ID du quiz doit être un entier'])
            ],
            'maxPlayers' => [
                new Assert\Optional([
                    new Assert\Type(['type' => 'integer', 'message' => 'Le nombre maximum de joueurs doit être un entier']),
                    new Assert\Range(['min' => 2, 'max' => 10, 'notInRangeMessage' => 'Le nombre de joueurs doit être entre 2 et 10'])
                ])
            ],
            'isTeamMode' => [
                new Assert\Optional([
                    new Assert\Type(['type' => 'bool', 'message' => 'Le mode équipe doit être un booléen'])
                ])
            ],
            'roomName' => [
                new Assert\Optional([
                    new Assert\Length(['max' => 255, 'maxMessage' => 'Le nom de la salle ne peut pas dépasser 255 caractères'])
                ])
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }

    private function validateAnswerData(array $data): void
    {
        $constraints = new Assert\Collection([
            'questionId' => [
                new Assert\NotBlank(['message' => 'L\'ID de la question est requis']),
                new Assert\Type(['type' => 'integer', 'message' => 'L\'ID de la question doit être un entier'])
            ],
            'answer' => [
                new Assert\NotBlank(['message' => 'La réponse est requise']),
                new Assert\Type(['type' => 'integer', 'message' => 'La réponse doit être un entier'])
            ],
            'timeSpent' => [
                new Assert\Optional([
                    new Assert\Type(['type' => 'integer', 'message' => 'Le temps passé doit être un entier'])
                ])
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }

    private function validateJoinRoomData(array $data): void
    {
        $constraints = new Assert\Collection([
            'teamName' => [
                new Assert\Optional([
                    new Assert\Length(['max' => 100, 'maxMessage' => 'Le nom de l\'équipe ne peut pas dépasser 100 caractères'])
                ])
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }

    private function validateInvitationData(array $data): void
    {
        $constraints = new Assert\Collection([
            'invitedUserIds' => [
                new Assert\NotBlank(['message' => 'Les utilisateurs à inviter sont requis']),
                new Assert\Type(['type' => 'array', 'message' => 'Les utilisateurs à inviter doivent être un tableau'])
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }
}
