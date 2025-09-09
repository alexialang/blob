<?php

namespace App\Tests\Unit\Controller;

use App\Controller\QuizController;
use App\Service\QuizRatingService;
use App\Service\QuizSearchService;
use App\Service\QuizCrudService;
use App\Service\LeaderboardService;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class QuizControllerTest extends TestCase
{
    public function testQuizControllerCanBeInstantiated(): void
    {
        $quizRatingService = $this->createMock(QuizRatingService::class);
        $quizSearchService = $this->createMock(QuizSearchService::class);
        $quizCrudService = $this->createMock(QuizCrudService::class);
        $leaderboardService = $this->createMock(LeaderboardService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $controller = new QuizController(
            $quizRatingService,
            $quizSearchService,
            $quizCrudService,
            $leaderboardService,
            $logger
        );
        $this->assertInstanceOf(QuizController::class, $controller);
    }

    public function testQuizControllerHasMethods(): void
    {
        $quizRatingService = $this->createMock(QuizRatingService::class);
        $quizSearchService = $this->createMock(QuizSearchService::class);
        $quizCrudService = $this->createMock(QuizCrudService::class);
        $leaderboardService = $this->createMock(LeaderboardService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $controller = new QuizController(
            $quizRatingService,
            $quizSearchService,
            $quizCrudService,
            $leaderboardService,
            $logger
        );
        
        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'show'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'delete'));
        $this->assertTrue(method_exists($controller, 'managementList'));
        $this->assertTrue(method_exists($controller, 'getOrganizedQuizzes'));
        $this->assertTrue(method_exists($controller, 'getAverageRating'));
        $this->assertTrue(method_exists($controller, 'getPublicLeaderboard'));
        $this->assertTrue(method_exists($controller, 'getQuizForEdit'));
    }
}
