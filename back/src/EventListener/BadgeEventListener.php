<?php

namespace App\EventListener;

use App\Event\QuizCompletedEvent;
use App\Event\QuizCreatedEvent;
use App\Service\BadgeService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class BadgeEventListener
{
    public function __construct(
        private readonly BadgeService $badgeService,
    ) {
    }

    #[AsEventListener(event: QuizCreatedEvent::NAME)]
    public function onQuizCreated(QuizCreatedEvent $event): void
    {
        $this->badgeService->initializeBadges();

        $user = $event->getUser();
        $quizCount = $user->getQuizs()->count();

        if (1 === $quizCount) {
            $this->badgeService->awardBadge($user, 'Premier Quiz');
        }

        if (10 === $quizCount) {
            $this->badgeService->awardBadge($user, 'Quiz Master');
        }
    }

    #[AsEventListener(event: QuizCompletedEvent::NAME)]
    public function onQuizCompleted(QuizCompletedEvent $event): void
    {
        $this->badgeService->initializeBadges();

        $user = $event->getUser();
        $userAnswer = $event->getUserAnswer();
        $score = $event->getScore();

        $uniqueQuizIds = [];
        foreach ($user->getUserAnswers() as $answer) {
            $quizId = $answer->getQuiz()?->getId();
            if ($quizId) {
                $uniqueQuizIds[$quizId] = true;
            }
        }
        $completedQuizCount = count($uniqueQuizIds);

        if (1 === $completedQuizCount) {
            $this->badgeService->awardBadge($user, 'Première Victoire');
        }

        if (null !== $score && $this->isPerfectScore($userAnswer)) {
            $this->badgeService->awardBadge($user, 'Expert');
        }

        if (50 === $completedQuizCount) {
            $this->badgeService->awardBadge($user, 'Joueur Assidu');
        }
    }

    private function isPerfectScore($userAnswer): bool
    {
        $quiz = $userAnswer->getQuiz();
        if (!$quiz) {
            return false;
        }

        $totalQuestions = $quiz->getQuestions()->count();
        $totalScore = $userAnswer->getTotalScore() ?? 0;

        return $totalScore === $totalQuestions;
    }
}
