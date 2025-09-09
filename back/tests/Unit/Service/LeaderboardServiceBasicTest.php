<?php

namespace App\Tests\Unit\Service;

use App\Entity\Quiz;
use App\Entity\User;
use App\Repository\UserAnswerRepository;
use App\Repository\UserRepository;
use App\Service\LeaderboardService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class LeaderboardServiceBasicTest extends TestCase
{
    private LeaderboardService $service;
    private UserAnswerRepository $userAnswerRepository;
    private UserRepository $userRepository;
    private UserService $userService;

    protected function setUp(): void
    {
        $this->userAnswerRepository = $this->createMock(UserAnswerRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userService = $this->createMock(UserService::class);
        
        $this->service = new LeaderboardService(
            $this->userAnswerRepository,
            $this->userRepository,
            $this->userService
        );
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(LeaderboardService::class, $this->service);
    }

    public function testGetQuizLeaderboardWithEmptyResults(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(123);
        
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(456);

        $this->userAnswerRepository->expects($this->once())
            ->method('findQuizLeaderboard')
            ->with(123)
            ->willReturn([]);

        $result = $this->service->getQuizLeaderboard($quiz, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('leaderboard', $result);
        $this->assertArrayHasKey('currentUserRank', $result);
        $this->assertEmpty($result['leaderboard']);
        $this->assertEquals(1, $result['currentUserRank']);
    }

    public function testGetQuizLeaderboardWithNullUser(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(123);

        $this->userAnswerRepository->expects($this->once())
            ->method('findQuizLeaderboard')
            ->with(123)
            ->willReturn([]);

        $result = $this->service->getQuizLeaderboard($quiz, null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('leaderboard', $result);
        $this->assertArrayHasKey('currentUserRank', $result);
        $this->assertEquals(1, $result['currentUserRank']);
    }

    public function testServiceHasRequiredMethods(): void
    {
        $this->assertTrue(method_exists($this->service, 'getQuizLeaderboard'));
    }
}
