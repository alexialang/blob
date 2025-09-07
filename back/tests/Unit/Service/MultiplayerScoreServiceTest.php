<?php

namespace App\Tests\Unit\Service;

use App\Entity\GameSession;
use App\Entity\Room;
use App\Entity\RoomPlayer;
use App\Entity\User;
use App\Service\MultiplayerScoreService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class MultiplayerScoreServiceTest extends TestCase
{
    private MultiplayerScoreService $service;

    protected function setUp(): void
    {
        $this->service = new MultiplayerScoreService();
        
        // Nettoyer le cache statique avant chaque test
        $this->clearStaticCache();
    }

    protected function tearDown(): void
    {
        // Nettoyer le cache statique après chaque test
        $this->clearStaticCache();
    }

    private function clearStaticCache(): void
    {
        $reflection = new \ReflectionClass(MultiplayerScoreService::class);
        $property = $reflection->getProperty('gameAnswers');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    public function testNormalizeScoreToPercentageValid(): void
    {
        $result = $this->service->normalizeScoreToPercentage(50, 10);
        
        // 50 points sur 10 questions (100 points max) = 50%
        $this->assertEquals(50, $result);
    }

    public function testNormalizeScoreToPercentageZeroQuestions(): void
    {
        $result = $this->service->normalizeScoreToPercentage(50, 0);
        
        $this->assertEquals(0, $result);
    }

    public function testNormalizeScoreToPercentageFullScore(): void
    {
        $result = $this->service->normalizeScoreToPercentage(100, 10);
        
        // 100 points sur 10 questions (100 points max) = 100%
        $this->assertEquals(100, $result);
    }

    public function testNormalizeScoreToPercentagePartialScore(): void
    {
        $result = $this->service->normalizeScoreToPercentage(25, 5);
        
        // 25 points sur 5 questions (50 points max) = 50%
        $this->assertEquals(50, $result);
    }

    public function testCalculatePointsCorrectFast(): void
    {
        $result = $this->service->calculatePoints(true, 5);
        
        // Réponse correcte en 5 secondes = 10 - floor(5/3) = 10 - 1 = 9 points
        $this->assertEquals(9, $result);
    }

    public function testCalculatePointsCorrectSlow(): void
    {
        $result = $this->service->calculatePoints(true, 25);
        
        // Réponse correcte en 25 secondes = 10 - floor(25/3) = 10 - 8 = 2 points
        $this->assertEquals(2, $result);
    }

    public function testCalculatePointsCorrectVerySlow(): void
    {
        $result = $this->service->calculatePoints(true, 30);
        
        // Réponse correcte en 30 secondes = 10 - floor(30/3) = 10 - 10 = 0, mais min 1 point
        $this->assertEquals(1, $result);
    }

    public function testCalculatePointsIncorrect(): void
    {
        $result = $this->service->calculatePoints(false, 5);
        
        // Réponse incorrecte = 0 points peu importe le temps
        $this->assertEquals(0, $result);
    }

    public function testRecordAnswer(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);
        
        $gameCode = 'GAME123';
        $questionId = 456;
        $isCorrect = true;
        $points = 8;

        $this->service->recordAnswer($gameCode, $user, $questionId, $isCorrect, $points);

        // Vérifier que la réponse a été enregistrée en calculant le score
        $totalScore = $this->service->calculateTotalScore($gameCode, $user);
        $this->assertEquals($points, $totalScore);
    }

    public function testRecordMultipleAnswers(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);
        
        $gameCode = 'GAME123';

        // Enregistrer plusieurs réponses
        $this->service->recordAnswer($gameCode, $user, 1, true, 8);
        $this->service->recordAnswer($gameCode, $user, 2, false, 0);
        $this->service->recordAnswer($gameCode, $user, 3, true, 6);

        $totalScore = $this->service->calculateTotalScore($gameCode, $user);
        $this->assertEquals(14, $totalScore); // 8 + 0 + 6
    }

    public function testCalculateTotalScoreNoAnswers(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(999);
        
        $gameCode = 'GAME999';

        $totalScore = $this->service->calculateTotalScore($gameCode, $user);
        $this->assertEquals(0, $totalScore);
    }

    public function testCalculateTotalScoreDifferentUsers(): void
    {
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(123);
        
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(456);
        
        $gameCode = 'GAME123';

        // Enregistrer des réponses pour différents utilisateurs
        $this->service->recordAnswer($gameCode, $user1, 1, true, 8);
        $this->service->recordAnswer($gameCode, $user2, 1, true, 6);
        $this->service->recordAnswer($gameCode, $user1, 2, true, 5);

        $totalScore1 = $this->service->calculateTotalScore($gameCode, $user1);
        $totalScore2 = $this->service->calculateTotalScore($gameCode, $user2);
        
        $this->assertEquals(13, $totalScore1); // 8 + 5
        $this->assertEquals(6, $totalScore2);  // 6
    }

    public function testUpdateLeaderboard(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $room = $this->createMock(Room::class);
        
        // Créer des utilisateurs mock
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        $user1->method('getPseudo')->willReturn('Player1');
        $user1->method('getFirstName')->willReturn('John');
        $user1->method('getLastName')->willReturn('Doe');
        
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);
        $user2->method('getPseudo')->willReturn('Player2');
        $user2->method('getFirstName')->willReturn('Jane');
        $user2->method('getLastName')->willReturn('Smith');

        // Créer des joueurs de salle mock
        $roomPlayer1 = $this->createMock(RoomPlayer::class);
        $roomPlayer1->method('getUser')->willReturn($user1);
        $roomPlayer1->method('getTeam')->willReturn('Team A');
        
        $roomPlayer2 = $this->createMock(RoomPlayer::class);
        $roomPlayer2->method('getUser')->willReturn($user2);
        $roomPlayer2->method('getTeam')->willReturn('Team B');

        $players = new ArrayCollection([$roomPlayer1, $roomPlayer2]);
        $room->method('getPlayers')->willReturn($players);

        $gameSession->method('getGameCode')->willReturn('GAME123');
        $gameSession->method('getRoom')->willReturn($room);
        $gameSession->method('getSharedScores')->willReturn([
            'Player1' => 85,
            'Player2' => 92
        ]);

        $leaderboard = $this->service->updateLeaderboard($gameSession);

        $this->assertIsArray($leaderboard);
        $this->assertCount(2, $leaderboard);
        
        // Vérifier que le leaderboard est trié par score décroissant
        $this->assertEquals(2, $leaderboard[0]['userId']); // Player2 avec 92 points
        $this->assertEquals('Player2', $leaderboard[0]['username']);
        $this->assertEquals(92, $leaderboard[0]['score']);
        $this->assertEquals(1, $leaderboard[0]['position']);
        
        $this->assertEquals(1, $leaderboard[1]['userId']); // Player1 avec 85 points
        $this->assertEquals('Player1', $leaderboard[1]['username']);
        $this->assertEquals(85, $leaderboard[1]['score']);
        $this->assertEquals(2, $leaderboard[1]['position']);
    }

    public function testUpdateLeaderboardWithNoSharedScores(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $room = $this->createMock(Room::class);
        
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        $user1->method('getPseudo')->willReturn('Player1');
        $user1->method('getFirstName')->willReturn('John');
        $user1->method('getLastName')->willReturn('Doe');

        $roomPlayer1 = $this->createMock(RoomPlayer::class);
        $roomPlayer1->method('getUser')->willReturn($user1);
        $roomPlayer1->method('getTeam')->willReturn('Team A');

        $players = new ArrayCollection([$roomPlayer1]);
        $room->method('getPlayers')->willReturn($players);

        $gameSession->method('getGameCode')->willReturn('GAME123');
        $gameSession->method('getRoom')->willReturn($room);
        $gameSession->method('getSharedScores')->willReturn(null);

        $leaderboard = $this->service->updateLeaderboard($gameSession);

        $this->assertIsArray($leaderboard);
        $this->assertCount(1, $leaderboard);
        $this->assertEquals(0, $leaderboard[0]['score']); // Score par défaut
    }

    public function testGetUserDisplayNameWithPseudo(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getPseudo')->willReturn('CoolPlayer');
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $user);
        $this->assertEquals('CoolPlayer', $result);
    }

    public function testGetUserDisplayNameWithFullName(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getPseudo')->willReturn(null);
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $user);
        $this->assertEquals('John Doe', $result);
    }

    public function testGetUserDisplayNameWithFirstNameOnly(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getPseudo')->willReturn(null);
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn(null);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $user);
        $this->assertEquals('John', $result);
    }

    public function testGetUserDisplayNameWithLastNameOnly(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getPseudo')->willReturn(null);
        $user->method('getFirstName')->willReturn(null);
        $user->method('getLastName')->willReturn('Doe');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $user);
        $this->assertEquals('Doe', $result);
    }

    public function testGetUserDisplayNameWithNoNames(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);
        $user->method('getPseudo')->willReturn(null);
        $user->method('getFirstName')->willReturn(null);
        $user->method('getLastName')->willReturn(null);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $user);
        $this->assertEquals('Joueur 123', $result);
    }

    public function testGetUserDisplayNameWithEmptyStrings(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(456);
        $user->method('getPseudo')->willReturn('');
        $user->method('getFirstName')->willReturn('');
        $user->method('getLastName')->willReturn('');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $user);
        $this->assertEquals('Joueur 456', $result);
    }

    public function testClearGameAnswers(): void
    {
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(123);
        
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(456);
        
        $gameCode1 = 'GAME123';
        $gameCode2 = 'GAME456';

        // Enregistrer des réponses pour différents jeux
        $this->service->recordAnswer($gameCode1, $user1, 1, true, 8);
        $this->service->recordAnswer($gameCode1, $user2, 1, true, 6);
        $this->service->recordAnswer($gameCode2, $user1, 1, true, 7);

        // Vérifier que les scores existent
        $this->assertEquals(8, $this->service->calculateTotalScore($gameCode1, $user1));
        $this->assertEquals(6, $this->service->calculateTotalScore($gameCode1, $user2));
        $this->assertEquals(7, $this->service->calculateTotalScore($gameCode2, $user1));

        // Nettoyer les réponses du premier jeu
        $this->service->clearGameAnswers($gameCode1);

        // Vérifier que seules les réponses du premier jeu ont été supprimées
        $this->assertEquals(0, $this->service->calculateTotalScore($gameCode1, $user1));
        $this->assertEquals(0, $this->service->calculateTotalScore($gameCode1, $user2));
        $this->assertEquals(7, $this->service->calculateTotalScore($gameCode2, $user1)); // Toujours là
    }

    public function testClearGameAnswersNoAnswers(): void
    {
        // Tester le nettoyage quand il n'y a pas de réponses
        $this->service->clearGameAnswers('NONEXISTENT');
        
        // Pas d'erreur attendue, juste s'assurer que ça ne plante pas
        $this->assertTrue(true);
    }
}
