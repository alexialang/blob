<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\UserAnswer;
use App\Event\QuizCompletedEvent;
use App\Event\QuizCreatedEvent;
use App\EventListener\BadgeEventListener;
use App\Service\BadgeService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class BadgeEventListenerTest extends TestCase
{
    private BadgeEventListener $listener;
    private BadgeService $badgeService;

    protected function setUp(): void
    {
        $this->badgeService = $this->createMock(BadgeService::class);
        $this->listener = new BadgeEventListener($this->badgeService);
    }

    // ===== Tests pour onQuizCreated() =====
    
    public function testOnQuizCreatedFirstQuiz(): void
    {
        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);
        
        $quizCollection = new ArrayCollection([$quiz]);
        
        $user->expects($this->once())
            ->method('getQuizs')
            ->willReturn($quizCollection);
        
        $this->badgeService
            ->expects($this->once())
            ->method('initializeBadges');
        
        $this->badgeService
            ->expects($this->once())
            ->method('awardBadge')
            ->with($user, 'Premier Quiz');
        
        $event = new QuizCreatedEvent($quiz, $user);
        
        $this->listener->onQuizCreated($event);
    }

    public function testOnQuizCreatedTenthQuiz(): void
    {
        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);
        
        // Simuler 10 quiz
        $quizzes = [];
        for ($i = 0; $i < 10; $i++) {
            $quizzes[] = $this->createMock(Quiz::class);
        }
        $quizCollection = new ArrayCollection($quizzes);
        
        $user->expects($this->once())
            ->method('getQuizs')
            ->willReturn($quizCollection);
        
        $this->badgeService
            ->expects($this->once())
            ->method('initializeBadges');
        
        $this->badgeService
            ->expects($this->once())
            ->method('awardBadge')
            ->with($user, 'Quiz Master');
        
        $event = new QuizCreatedEvent($quiz, $user);
        
        $this->listener->onQuizCreated($event);
    }

    public function testOnQuizCreatedNoSpecialBadge(): void
    {
        $user = $this->createMock(User::class);
        $quiz = $this->createMock(Quiz::class);
        
        // Simuler 5 quiz (ni 1 ni 10)
        $quizzes = [];
        for ($i = 0; $i < 5; $i++) {
            $quizzes[] = $this->createMock(Quiz::class);
        }
        $quizCollection = new ArrayCollection($quizzes);
        
        $user->expects($this->once())
            ->method('getQuizs')
            ->willReturn($quizCollection);
        
        $this->badgeService
            ->expects($this->once())
            ->method('initializeBadges');
        
        $this->badgeService
            ->expects($this->never())
            ->method('awardBadge');
        
        $event = new QuizCreatedEvent($quiz, $user);
        
        $this->listener->onQuizCreated($event);
    }

    // ===== Tests pour onQuizCompleted() =====
    
    public function testOnQuizCompletedFirstCompletion(): void
    {
        $user = $this->createMock(User::class);
        $userAnswer = $this->createMock(UserAnswer::class);
        $quiz = $this->createMock(Quiz::class);
        
        $quiz->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        
        $userAnswer->expects($this->atLeastOnce())
            ->method('getQuiz')
            ->willReturn($quiz);
        
        $userAnswer->expects($this->atLeastOnce())
            ->method('getTotalScore')
            ->willReturn(85);
        
        // Simuler une seule réponse (premier quiz complété)
        $userAnswerCollection = new ArrayCollection([$userAnswer]);
        
        $user->expects($this->once())
            ->method('getUserAnswers')
            ->willReturn($userAnswerCollection);
        
        $this->badgeService
            ->expects($this->once())
            ->method('initializeBadges');
        
        $this->badgeService
            ->expects($this->once())
            ->method('awardBadge')
            ->with($user, 'Première Victoire');
        
        $event = new QuizCompletedEvent($userAnswer, $user);
        
        $this->listener->onQuizCompleted($event);
    }

    public function testOnQuizCompletedFiftiethCompletion(): void
    {
        $user = $this->createMock(User::class);
        $userAnswer = $this->createMock(UserAnswer::class);
        
        $userAnswer->expects($this->once())
            ->method('getTotalScore')
            ->willReturn(75);
        
        // Simuler 50 réponses de quiz différents
        $userAnswers = [];
        for ($i = 1; $i <= 50; $i++) {
            $answer = $this->createMock(UserAnswer::class);
            $quiz = $this->createMock(Quiz::class);
            $quiz->method('getId')->willReturn($i);
            $answer->method('getQuiz')->willReturn($quiz);
            $userAnswers[] = $answer;
        }
        $userAnswerCollection = new ArrayCollection($userAnswers);
        
        $user->expects($this->once())
            ->method('getUserAnswers')
            ->willReturn($userAnswerCollection);
        
        $this->badgeService
            ->expects($this->once())
            ->method('initializeBadges');
        
        $this->badgeService
            ->expects($this->once())
            ->method('awardBadge')
            ->with($user, 'Joueur Assidu');
        
        $event = new QuizCompletedEvent($userAnswer, $user);
        
        $this->listener->onQuizCompleted($event);
    }

    public function testOnQuizCompletedPerfectScore(): void
    {
        $user = $this->createMock(User::class);
        $userAnswer = $this->createMock(UserAnswer::class);
        $quiz = $this->createMock(Quiz::class);
        
        $quiz->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        
        $userAnswer->expects($this->atLeastOnce())
            ->method('getQuiz')
            ->willReturn($quiz);
        
        $userAnswer->expects($this->atLeastOnce())
            ->method('getTotalScore')
            ->willReturn(5);
        
        // Simuler un quiz avec 5 questions pour un score parfait
        $questions = new ArrayCollection([]);
        for ($i = 0; $i < 5; $i++) {
            $questions->add($this->createMock(\App\Entity\Question::class));
        }
        
        $quiz->expects($this->once())
            ->method('getQuestions')
            ->willReturn($questions);
        
        // Simuler une seule réponse
        $userAnswerCollection = new ArrayCollection([$userAnswer]);
        
        $user->expects($this->once())
            ->method('getUserAnswers')
            ->willReturn($userAnswerCollection);
        
        $this->badgeService
            ->expects($this->once())
            ->method('initializeBadges');
        
        $this->badgeService
            ->expects($this->exactly(2))
            ->method('awardBadge')
            ->withConsecutive(
                [$user, 'Première Victoire'],
                [$user, 'Expert']
            );
        
        $event = new QuizCompletedEvent($userAnswer, $user);
        
        $this->listener->onQuizCompleted($event);
    }


    public function testOnQuizCompletedNoSpecialScore(): void
    {
        $user = $this->createMock(User::class);
        $userAnswer = $this->createMock(UserAnswer::class);
        $quiz = $this->createMock(Quiz::class);
        
        $quiz->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        
        $userAnswer->expects($this->atLeastOnce())
            ->method('getQuiz')
            ->willReturn($quiz);
        
        $userAnswer->expects($this->atLeastOnce())
            ->method('getTotalScore')
            ->willReturn(70);
        
        // Simuler une seule réponse
        $userAnswerCollection = new ArrayCollection([$userAnswer]);
        
        $user->expects($this->once())
            ->method('getUserAnswers')
            ->willReturn($userAnswerCollection);
        
        $this->badgeService
            ->expects($this->once())
            ->method('initializeBadges');
        
        $this->badgeService
            ->expects($this->once())
            ->method('awardBadge')
            ->with($user, 'Première Victoire');
        
        $event = new QuizCompletedEvent($userAnswer, $user);
        
        $this->listener->onQuizCompleted($event);
    }

    public function testOnQuizCompletedWithNullQuiz(): void
    {
        $user = $this->createMock(User::class);
        $userAnswer = $this->createMock(UserAnswer::class);
        
        $userAnswer->expects($this->once())
            ->method('getTotalScore')
            ->willReturn(80);
        
        // Simuler une réponse avec quiz null
        $userAnswerWithNullQuiz = $this->createMock(UserAnswer::class);
        $userAnswerWithNullQuiz->method('getQuiz')->willReturn(null);
        
        $userAnswerCollection = new ArrayCollection([$userAnswerWithNullQuiz]);
        
        $user->expects($this->once())
            ->method('getUserAnswers')
            ->willReturn($userAnswerCollection);
        
        $this->badgeService
            ->expects($this->once())
            ->method('initializeBadges');
        
        $this->badgeService
            ->expects($this->never())
            ->method('awardBadge');
        
        $event = new QuizCompletedEvent($userAnswer, $user);
        
        $this->listener->onQuizCompleted($event);
    }
}
