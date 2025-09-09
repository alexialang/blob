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
        $this->assertTrue(method_exists($controller, 'publish'));
        $this->assertTrue(method_exists($controller, 'unpublish'));
        $this->assertTrue(method_exists($controller, 'duplicate'));
        $this->assertTrue(method_exists($controller, 'getUserQuizzes'));
        $this->assertTrue(method_exists($controller, 'getPublicQuizzes'));
        $this->assertTrue(method_exists($controller, 'searchQuizzes'));
        $this->assertTrue(method_exists($controller, 'getQuizStatistics'));
    }
}
