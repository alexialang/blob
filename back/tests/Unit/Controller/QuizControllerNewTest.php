<?php

namespace App\Tests\Unit\Controller;

use App\Controller\QuizController;
use App\Service\LeaderboardService;
use Psr\Log\LoggerInterface;
use App\Service\QuizCrudService;
use App\Service\QuizRatingService;
use App\Service\QuizSearchService;
use PHPUnit\Framework\TestCase;

class QuizControllerNewTest extends TestCase
{
    private QuizRatingService $quizRatingService;
    private QuizSearchService $quizSearchService;
    private QuizCrudService $quizCrudService;
    private LeaderboardService $leaderboardService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->quizRatingService = $this->createMock(QuizRatingService::class);
        $this->quizSearchService = $this->createMock(QuizSearchService::class);
        $this->quizCrudService = $this->createMock(QuizCrudService::class);
        $this->leaderboardService = $this->createMock(LeaderboardService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testQuizControllerCanBeInstantiated(): void
    {
        $controller = new QuizController($this->quizRatingService, $this->quizSearchService, $this->quizCrudService, $this->leaderboardService, $this->logger);
        $this->assertInstanceOf(QuizController::class, $controller);
    }

    public function testQuizControllerHasMethods(): void
    {
        $controller = new QuizController($this->quizRatingService, $this->quizSearchService, $this->quizCrudService, $this->leaderboardService, $this->logger);
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
