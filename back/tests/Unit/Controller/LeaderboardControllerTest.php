<?php

namespace App\Tests\Unit\Controller;

use App\Controller\LeaderboardController;
use App\Service\LeaderboardService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class LeaderboardControllerTest extends TestCase
{
    private LeaderboardService $leaderboardService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->leaderboardService = $this->createMock(LeaderboardService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testLeaderboardControllerCanBeInstantiated(): void
    {
        $controller = new LeaderboardController($this->leaderboardService, $this->entityManager);
        $this->assertInstanceOf(LeaderboardController::class, $controller);
    }

    public function testLeaderboardControllerHasMethods(): void
    {
        $controller = new LeaderboardController($this->leaderboardService, $this->entityManager);
        $this->assertTrue(method_exists($controller, 'getGeneralLeaderboard'));
        $this->assertTrue(method_exists($controller, 'getQuizLeaderboard'));
    }
}
