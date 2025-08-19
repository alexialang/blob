<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserAnswer;
use App\Entity\Question;
use App\Repository\QuizRepository;
use App\Repository\UserAnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class GameService
{
    private EntityManagerInterface $em;
    private QuizRepository $quizRepository;
    private UserAnswerRepository $userAnswerRepository;
    private array $gameSessions = [];

    public function __construct(
        EntityManagerInterface $em,
        QuizRepository $quizRepository,
        UserAnswerRepository $userAnswerRepository,
        ValidatorInterface $validator
    ) {
        $this->em = $em;
        $this->quizRepository = $quizRepository;
        $this->userAnswerRepository = $userAnswerRepository;
        $this->validator = $validator;
    }

    public function startGame(int $quizId, User $user): array
    {
        $quiz = $this->quizRepository->find($quizId);
        if (!$quiz) {
            throw new BadRequestException('Quiz non trouvé');
        }

        $questions = $quiz->getQuestions()->toArray();
        if (empty($questions)) {
            throw new BadRequestException('Le quiz ne contient aucune question');
        }

        shuffle($questions);

        $sessionId = uniqid('game_session_');
        $this->gameSessions[$sessionId] = [
            'quiz' => $quiz,
            'user' => $user,
            'questions' => $questions,
            'currentQuestionIndex' => 0,
            'answers' => [],
            'score' => 0,
            'startTime' => new \DateTimeImmutable(),
            'status' => 'active'
        ];

        return [
            'sessionId' => $sessionId,
            'quiz' => [
                'id' => $quiz->getId(),
                'title' => $quiz->getTitle(),
                'description' => $quiz->getDescription(),
                'questionCount' => count($questions)
            ],
            'status' => 'started'
        ];
    }

    public function getCurrentQuestion(string $sessionId, User $user): array
    {
        if (!isset($this->gameSessions[$sessionId])) {
            throw new BadRequestException('Session de jeu non trouvée');
        }

        $session = $this->gameSessions[$sessionId];
        
        if ($session['user']->getId() !== $user->getId()) {
            throw new BadRequestException('Accès non autorisé à cette session');
        }

        if ($session['status'] !== 'active') {
            throw new BadRequestException('La session de jeu n\'est pas active');
        }

        $currentIndex = $session['currentQuestionIndex'];
        $questions = $session['questions'];

        if ($currentIndex >= count($questions)) {
            return [
                'finished' => true,
                'message' => 'Toutes les questions ont été répondues'
            ];
        }

        $question = $questions[$currentIndex];
        $answers = $question->getAnswers()->toArray();
        
        if ($question->getTypeQuestion()->getName() !== 'right_order') {
            shuffle($answers);
        }

        $formattedAnswers = [];
        foreach ($answers as $answer) {
            $formattedAnswers[] = [
                'id' => $answer->getId(),
                'text' => $answer->getAnswer(),
                'pairId' => $answer->getPairId(),
                'orderCorrect' => $answer->getOrderCorrect()
            ];
        }

        return [
            'questionId' => $question->getId(),
            'question' => $question->getQuestion(),
            'type' => $question->getTypeQuestion()->getName(),
            'answers' => $formattedAnswers,
            'currentIndex' => $currentIndex + 1,
            'totalQuestions' => count($questions),
            'timeLimit' => 50,
            'finished' => false
        ];
    }

    public function submitAnswer(string $sessionId, User $user, array $data): array
    {
        $this->validateGameAnswerData($data);
        if (!isset($this->gameSessions[$sessionId])) {
            throw new BadRequestException('Session de jeu non trouvée');
        }

        $session = &$this->gameSessions[$sessionId];
        
        if ($session['user']->getId() !== $user->getId()) {
            throw new BadRequestException('Accès non autorisé à cette session');
        }

        if ($session['status'] !== 'active') {
            throw new BadRequestException('La session de jeu n\'est pas active');
        }

        $currentIndex = $session['currentQuestionIndex'];
        $questions = $session['questions'];

        if ($currentIndex >= count($questions)) {
            throw new BadRequestException('Toutes les questions ont été répondues');
        }

        $question = $questions[$currentIndex];
        $userAnswers = $data['answers'] ?? [];
        $timeSpent = $data['timeSpent'] ?? 30;

        $score = $this->calculateScore($question, $userAnswers, $timeSpent);
        
        $session['answers'][$currentIndex] = [
            'questionId' => $question->getId(),
            'userAnswers' => $userAnswers,
            'score' => $score,
            'timeSpent' => $timeSpent,
            'correct' => $score > 0
        ];

        $totalQuestions = count($session['questions']);
        $correctAnswers = 0;
        
        foreach ($session['answers'] as $answer) {
            if ($answer['correct']) {
                $correctAnswers++;
            }
        }
        
        $normalizedScore = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;
        
        $session['normalizedScore'] = $normalizedScore;
        $session['currentQuestionIndex']++;

        $isFinished = $session['currentQuestionIndex'] >= count($questions);

        if ($isFinished) {
            $session['status'] = 'finished';
            $session['endTime'] = new \DateTimeImmutable();
            
            try {
                $this->saveGameResults($sessionId);
                error_log("Game results saved successfully for session: $sessionId");
            } catch (\Exception $e) {
                error_log("FAILED to save game results for session: $sessionId - " . $e->getMessage());
            }
        }

        return [
            'correct' => $score > 0,
            'score' => $score,
            'totalScore' => $session['normalizedScore'],
            'rawTotalScore' => $session['score'],
            'finished' => $isFinished,
            'nextQuestion' => !$isFinished,
            'savedToDatabase' => true
        ];
    }

    public function getResults(string $sessionId, User $user): array
    {
        if (!isset($this->gameSessions[$sessionId])) {
            throw new BadRequestException('Session de jeu non trouvée');
        }

        $session = $this->gameSessions[$sessionId];
        
        if ($session['user']->getId() !== $user->getId()) {
            throw new BadRequestException('Accès non autorisé à cette session');
        }

        $totalQuestions = count($session['questions']);
        $answeredQuestions = count($session['answers']);
        $correctAnswers = 0;
        $totalTimeSpent = 0;

        foreach ($session['answers'] as $answer) {
            if ($answer['correct']) {
                $correctAnswers++;
            }
            $totalTimeSpent += $answer['timeSpent'];
        }

        $normalizedTotalScore = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;
        
        return [
            'quiz' => [
                'id' => $session['quiz']->getId(),
                'title' => $session['quiz']->getTitle()
            ],
            'totalScore' => $normalizedTotalScore,
            'rawTotalScore' => $session['score'],
            'totalQuestions' => $totalQuestions,
            'answeredQuestions' => $answeredQuestions,
            'correctAnswers' => $correctAnswers,
            'percentage' => $normalizedTotalScore,
            'totalTimeSpent' => $totalTimeSpent,
            'averageTimePerQuestion' => $answeredQuestions > 0 ? round($totalTimeSpent / $answeredQuestions, 2) : 0,
            'status' => $session['status'],
            'startTime' => $session['startTime']->format('Y-m-d H:i:s'),
            'endTime' => isset($session['endTime']) ? $session['endTime']->format('Y-m-d H:i:s') : null
        ];
    }

    public function finishGame(string $sessionId, User $user): array
    {
        if (!isset($this->gameSessions[$sessionId])) {
            throw new BadRequestException('Session de jeu non trouvée');
        }

        $session = &$this->gameSessions[$sessionId];
        
        if ($session['user']->getId() !== $user->getId()) {
            throw new BadRequestException('Accès non autorisé à cette session');
        }

        $session['status'] = 'finished';
        $session['endTime'] = new \DateTimeImmutable();
        
        $this->saveGameResults($sessionId);
        
        return $this->getResults($sessionId, $user);
    }

    public function getGameStatus(string $sessionId, User $user): array
    {
        if (!isset($this->gameSessions[$sessionId])) {
            throw new BadRequestException('Session de jeu non trouvée');
        }

        $session = $this->gameSessions[$sessionId];
        
        if ($session['user']->getId() !== $user->getId()) {
            throw new BadRequestException('Accès non autorisé à cette session');
        }

        return [
            'status' => $session['status'],
            'currentQuestionIndex' => $session['currentQuestionIndex'],
            'totalQuestions' => count($session['questions']),
            'score' => $session['score'],
            'answeredQuestions' => count($session['answers'])
        ];
    }

    private function calculateScore(Question $question, array $userAnswers, int $timeSpent): int
    {
        $correctAnswers = $question->getAnswers()->filter(function($answer) {
            return $answer->isCorrect();
        })->toArray();

        $typeQuestion = $question->getTypeQuestion()->getName();
        $baseScore = 100;

        switch ($typeQuestion) {
            case 'multiple_choice':
            case 'true_false':
                $correctAnswerIds = array_map(fn($answer) => $answer->getId(), $correctAnswers);
                $userAnswerIds = is_array($userAnswers) ? $userAnswers : [$userAnswers];
                $isCorrect = !empty(array_intersect($correctAnswerIds, $userAnswerIds));
                return $isCorrect ? $this->applyTimeBonus($baseScore, $timeSpent) : 0;

            case 'find_the_intruder':
                $intruder = $question->getAnswers()->filter(function($answer) {
                    return $answer->isIntrus();
                })->first();
                return $intruder && in_array($intruder->getId(), $userAnswers) ? $this->applyTimeBonus($baseScore, $timeSpent) : 0;

            case 'matching':
                return $this->calculateMatchingScore($question, $userAnswers, $timeSpent);

            case 'right_order':
                return $this->calculateOrderScore($question, $userAnswers, $timeSpent);

            default:
                return 0;
        }
    }

    private function calculateMatchingScore(Question $question, array $userAnswers, int $timeSpent): int
    {
        $answers = $question->getAnswers()->toArray();
        $correctPairs = [];
        
        foreach ($answers as $answer) {
            if ($answer->getPairId()) {
                $correctPairs[$answer->getPairId()] = $answer->getId();
            }
        }

        $correctMatches = 0;
        foreach ($userAnswers as $userPair) {
            if (isset($userPair['left']) && isset($userPair['right'])) {
                $leftId = $userPair['left'];
                $rightId = $userPair['right'];
                
                foreach ($correctPairs as $pairId => $answerId) {
                    if ($answerId === $leftId || $answerId === $rightId) {
                        $correspondingPairId = str_replace(['left_', 'right_'], ['right_', 'left_'], $pairId);
                        if (isset($correctPairs[$correspondingPairId])) {
                            $correspondingAnswerId = $correctPairs[$correspondingPairId];
                            if (($answerId === $leftId && $correspondingAnswerId === $rightId) ||
                                ($answerId === $rightId && $correspondingAnswerId === $leftId)) {
                                $correctMatches++;
                                break;
                            }
                        }
                    }
                }
            }
        }

        $totalPairs = count($correctPairs) / 2;
        $score = $totalPairs > 0 ? (int)(($correctMatches / $totalPairs) * 100) : 0;
        
        return $this->applyTimeBonus($score, $timeSpent);
    }

    private function calculateOrderScore(Question $question, array $userAnswers, int $timeSpent): int
    {
        $answers = $question->getAnswers()->toArray();
        $correctOrder = [];
        
        foreach ($answers as $answer) {
            $correctOrder[$answer->getOrderCorrect()] = $answer->getId();
        }
        
        ksort($correctOrder);
        $correctSequence = array_values($correctOrder);
        
        $score = 0;
        if ($userAnswers === $correctSequence) {
            $score = 100;
        } else {
            $correctPositions = 0;
            for ($i = 0; $i < min(count($userAnswers), count($correctSequence)); $i++) {
                if ($userAnswers[$i] === $correctSequence[$i]) {
                    $correctPositions++;
                }
            }
            $score = (int)(($correctPositions / count($correctSequence)) * 100);
        }
        
        return $this->applyTimeBonus($score, $timeSpent);
    }

    private function applyTimeBonus(int $baseScore, int $timeSpent): int
    {
        $timeBonus = max(0, (30 - $timeSpent) / 30);
        return (int)($baseScore * (1 + $timeBonus * 0.2));
    }

    private function saveGameResults(string $sessionId): void
    {
        $session = $this->gameSessions[$sessionId];
        
        try {
            $totalQuestions = count($session['questions']);
            $correctAnswers = 0;
            
            foreach ($session['answers'] as $answer) {
                if ($answer['correct']) {
                    $correctAnswers++;
                }
            }
            
            $finalScore = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;
            error_log("Game session completed: User {$session['user']->getId()}, Quiz {$session['quiz']->getId()}, Correct Answers: {$correctAnswers}/{$totalQuestions}, Final Score: {$finalScore}/100");
        } catch (\Exception $e) {
            error_log("Error processing game session: " . $e->getMessage());
            throw $e;
        }
    }

    private function validateGameAnswerData(array $data): void
    {
        $constraints = new Assert\Collection([
            'answer' => [
                new Assert\NotBlank(['message' => 'La réponse est requise']),
                new Assert\Length(['max' => 1000, 'maxMessage' => 'La réponse ne peut pas dépasser 1000 caractères'])
            ],
            'question_id' => [
                new Assert\NotBlank(['message' => 'L\'ID de la question est requis']),
                new Assert\Type(['type' => 'integer', 'message' => 'L\'ID de la question doit être un entier'])
            ],
            'time_spent' => [
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
}
