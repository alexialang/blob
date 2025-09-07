<?php

namespace App\Tests\Unit\Service;

use App\Entity\Quiz;
use App\Entity\User;
use App\Repository\UserAnswerRepository;
use App\Repository\UserRepository;
use App\Service\LeaderboardService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class LeaderboardServiceTest extends TestCase
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

    // ===== Tests pour getQuizLeaderboard() =====
    
    public function testGetQuizLeaderboardWithCurrentUser(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $currentUser = $this->createMock(User::class);

        $quiz->method('getId')->willReturn(123);
        $currentUser->method('getId')->willReturn(456);

        $results = [
            [
                'userId' => 789,
                'name' => 'John Doe',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'company' => 'Tech Corp',
                'score' => 95
            ],
            [
                'userId' => 456, // Current user
                'name' => 'Jane Smith',
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'company' => 'Dev Inc',
                'score' => 87
            ],
            [
                'userId' => 321,
                'name' => null,
                'firstName' => 'Bob',
                'lastName' => 'Wilson',
                'company' => null,
                'score' => 75
            ]
        ];

        $this->userAnswerRepository->expects($this->once())
            ->method('findQuizLeaderboard')
            ->with(123)
            ->willReturn($results);

        // Mock pour getCurrentUserQuizScore
        $this->userAnswerRepository->expects($this->once())
            ->method('getUserMaxScoreForQuiz')
            ->with(123, 456)
            ->willReturn(87);

        $result = $this->service->getQuizLeaderboard($quiz, $currentUser);

        $this->assertArrayHasKey('leaderboard', $result);
        $this->assertArrayHasKey('currentUserRank', $result);
        $this->assertArrayHasKey('totalPlayers', $result);
        $this->assertArrayHasKey('currentUserScore', $result);

        $this->assertCount(3, $result['leaderboard']);
        $this->assertSame(2, $result['currentUserRank']); // Current user is 2nd
        $this->assertSame(3, $result['totalPlayers']);

        // Vérifier le premier utilisateur
        $this->assertSame(1, $result['leaderboard'][0]['rank']);
        $this->assertSame('John Doe', $result['leaderboard'][0]['name']);
        $this->assertSame('Tech Corp', $result['leaderboard'][0]['company']);
        $this->assertSame(95, $result['leaderboard'][0]['score']);
        $this->assertFalse($result['leaderboard'][0]['isCurrentUser']);

        // Vérifier l'utilisateur courant
        $this->assertSame(2, $result['leaderboard'][1]['rank']);
        $this->assertSame('Jane Smith', $result['leaderboard'][1]['name']);
        $this->assertSame('Dev Inc', $result['leaderboard'][1]['company']);
        $this->assertSame(87, $result['leaderboard'][1]['score']);
        $this->assertTrue($result['leaderboard'][1]['isCurrentUser']);

        // Vérifier l'utilisateur avec nom null
        $this->assertSame(3, $result['leaderboard'][2]['rank']);
        $this->assertSame('Bob Wilson', $result['leaderboard'][2]['name']);
        $this->assertSame('Aucune entreprise', $result['leaderboard'][2]['company']);
        $this->assertSame(75, $result['leaderboard'][2]['score']);
        $this->assertFalse($result['leaderboard'][2]['isCurrentUser']);
    }

    public function testGetQuizLeaderboardWithoutCurrentUser(): void
    {
        $quiz = $this->createMock(Quiz::class);

        $quiz->method('getId')->willReturn(123);

        $results = [
            [
                'userId' => 789,
                'name' => 'John Doe',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'company' => 'Tech Corp',
                'score' => 95
            ]
        ];

        $this->userAnswerRepository->expects($this->once())
            ->method('findQuizLeaderboard')
            ->with(123)
            ->willReturn($results);

        // Pas de mock pour getUserMaxScoreForQuiz car currentUser est null

        $result = $this->service->getQuizLeaderboard($quiz, null);

        $this->assertArrayHasKey('leaderboard', $result);
        $this->assertArrayHasKey('currentUserRank', $result);
        $this->assertArrayHasKey('totalPlayers', $result);
        $this->assertArrayHasKey('currentUserScore', $result);

        $this->assertCount(1, $result['leaderboard']);
        $this->assertSame(2, $result['currentUserRank']); // count + 1 when no current user
        $this->assertSame(1, $result['totalPlayers']);

        $this->assertFalse($result['leaderboard'][0]['isCurrentUser']);
    }

    public function testGetQuizLeaderboardWithAnonymousUser(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $currentUser = $this->createMock(User::class);

        $quiz->method('getId')->willReturn(123);
        $currentUser->method('getId')->willReturn(456);

        $results = [
            [
                'userId' => 789,
                'name' => '',
                'firstName' => '',
                'lastName' => '',
                'company' => 'Tech Corp',
                'score' => 95
            ]
        ];

        $this->userAnswerRepository->expects($this->once())
            ->method('findQuizLeaderboard')
            ->with(123)
            ->willReturn($results);

        // Mock pour getCurrentUserQuizScore
        $this->userAnswerRepository->expects($this->once())
            ->method('getUserMaxScoreForQuiz')
            ->with(123, 456)
            ->willReturn(0);

        $result = $this->service->getQuizLeaderboard($quiz, $currentUser);

        $this->assertSame('Joueur anonyme', $result['leaderboard'][0]['name']);
    }

    public function testGetQuizLeaderboardWithMoreThan10Results(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $currentUser = $this->createMock(User::class);

        $quiz->method('getId')->willReturn(123);
        $currentUser->method('getId')->willReturn(456);

        // Créer 15 résultats
        $results = [];
        for ($i = 1; $i <= 15; $i++) {
            $results[] = [
                'userId' => $i,
                'name' => "User $i",
                'firstName' => "First $i",
                'lastName' => "Last $i",
                'company' => "Company $i",
                'score' => 100 - $i
            ];
        }

        $this->userAnswerRepository->expects($this->once())
            ->method('findQuizLeaderboard')
            ->with(123)
            ->willReturn($results);

        // Mock pour getCurrentUserQuizScore
        $this->userAnswerRepository->expects($this->once())
            ->method('getUserMaxScoreForQuiz')
            ->with(123, 456)
            ->willReturn(0);

        $result = $this->service->getQuizLeaderboard($quiz, $currentUser);

        // Seulement les 10 premiers doivent être retournés
        $this->assertCount(10, $result['leaderboard']);
        $this->assertSame(15, $result['totalPlayers']);
        $this->assertSame(16, $result['currentUserRank']); // count + 1 car pas trouvé
    }

    public function testGetQuizLeaderboardEmpty(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $currentUser = $this->createMock(User::class);

        $quiz->method('getId')->willReturn(123);
        $currentUser->method('getId')->willReturn(456);

        $this->userAnswerRepository->expects($this->once())
            ->method('findQuizLeaderboard')
            ->with(123)
            ->willReturn([]);

        // Mock pour getCurrentUserQuizScore
        $this->userAnswerRepository->expects($this->once())
            ->method('getUserMaxScoreForQuiz')
            ->with(123, 456)
            ->willReturn(0);

        $result = $this->service->getQuizLeaderboard($quiz, $currentUser);

        $this->assertCount(0, $result['leaderboard']);
        $this->assertSame(1, $result['currentUserRank']); // 0 + 1
        $this->assertSame(0, $result['totalPlayers']);
    }

    // ===== Tests pour getGeneralLeaderboard() =====
    
    public function testGetGeneralLeaderboardWithCurrentUser(): void
    {
        $currentUser = $this->createMock(User::class);
        $user1 = $this->createMock(User::class);

        $currentUser->method('getId')->willReturn(456);
        $user1->method('getId')->willReturn(456); // Same as current user

        $user1->method('getPseudo')->willReturn('JohnDoe');
        $user1->method('getFirstName')->willReturn('John');
        $user1->method('getLastName')->willReturn('Doe');
        $user1->method('getAvatar')->willReturn(null);

        $users = [$user1];

        $this->userRepository->expects($this->once())
            ->method('findActiveUsersForLeaderboard')
            ->willReturn($users);

        // Mock UserService calls
        $this->userService->expects($this->once())
            ->method('getUserStatistics')
            ->with($user1)
            ->willReturn([
                'totalScore' => 950,
                'averageScore' => 95,
                'totalQuizzesCompleted' => 10,
                'totalAttempts' => 12,
                'badgesEarned' => 5,
                'memberSince' => '2023-01-01'
            ]);

        $result = $this->service->getGeneralLeaderboard(10, $currentUser);

        $this->assertArrayHasKey('leaderboard', $result);
        $this->assertArrayHasKey('currentUser', $result);
        $this->assertArrayHasKey('meta', $result);

        $this->assertCount(1, $result['leaderboard']);
        $this->assertSame(1, $result['meta']['totalUsers']);

        // Vérifier l'utilisateur courant
        $this->assertSame(456, $result['leaderboard'][0]['id']);
        $this->assertSame(1, $result['leaderboard'][0]['position']);
        $this->assertSame(950, $result['leaderboard'][0]['totalScore']);
        $this->assertTrue($result['leaderboard'][0]['isCurrentUser']);

        // Vérifier que la position du current user est correcte
        $this->assertIsInt($result['currentUser']['position']);
    }

    public function testGetGeneralLeaderboardWithoutCurrentUser(): void
    {
        $user1 = $this->createMock(User::class);

        $user1->method('getId')->willReturn(789);
        $user1->method('getPseudo')->willReturn('JohnDoe');
        $user1->method('getFirstName')->willReturn('John');
        $user1->method('getLastName')->willReturn('Doe');
        $user1->method('getAvatar')->willReturn(null);

        $users = [$user1];

        $this->userRepository->expects($this->once())
            ->method('findActiveUsersForLeaderboard')
            ->willReturn($users);

        $this->userService->expects($this->once())
            ->method('getUserStatistics')
            ->with($user1)
            ->willReturn([
                'totalScore' => 950,
                'averageScore' => 95,
                'totalQuizzesCompleted' => 10,
                'totalAttempts' => 12,
                'badgesEarned' => 5,
                'memberSince' => '2023-01-01'
            ]);

        $result = $this->service->getGeneralLeaderboard(10, null);

        $this->assertCount(1, $result['leaderboard']);
        $this->assertSame(2, $result['currentUser']['position']); // totalUsers + 1
        $this->assertNull($result['currentUser']['data']);
        $this->assertSame(1, $result['meta']['totalUsers']);

        $this->assertFalse($result['leaderboard'][0]['isCurrentUser']);
    }

    public function testGetGeneralLeaderboardEmpty(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findActiveUsersForLeaderboard')
            ->willReturn([]);

        $result = $this->service->getGeneralLeaderboard(10, null);

        $this->assertCount(0, $result['leaderboard']);
        $this->assertSame(1, $result['currentUser']['position']);
        $this->assertSame(0, $result['meta']['totalUsers']);
    }
}
