<?php

namespace App\Tests\Unit\Repository;

use App\Repository\UserAnswerRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class UserAnswerRepositoryTest extends TestCase
{
    private ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
    }

    public function testUserAnswerRepositoryCanBeInstantiated(): void
    {
        $repository = new UserAnswerRepository($this->managerRegistry);
        $this->assertInstanceOf(UserAnswerRepository::class, $repository);
    }

    public function testUserAnswerRepositoryHasMethods(): void
    {
        $repository = new UserAnswerRepository($this->managerRegistry);
        $this->assertTrue(method_exists($repository, 'findQuizLeaderboard'));
        $this->assertTrue(method_exists($repository, 'findMaxScoreForUserAndQuiz'));
        $this->assertTrue(method_exists($repository, 'getQuizLeaderboardData'));
        $this->assertTrue(method_exists($repository, 'getGeneralLeaderboardData'));
        $this->assertTrue(method_exists($repository, 'getUserMaxScoreForQuiz'));
        $this->assertTrue(method_exists($repository, 'getUserTotalScore'));
        $this->assertTrue(method_exists($repository, 'getUsersWithBetterScoreCount'));
        $this->assertTrue(method_exists($repository, 'getTotalActiveUsersCount'));
        $this->assertTrue(method_exists($repository, 'getUserStats'));
        $this->assertTrue(method_exists($repository, 'getUserAnswersWithQuizData'));
    }
}
