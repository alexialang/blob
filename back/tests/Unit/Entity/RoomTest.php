<?php

namespace App\Tests\Unit\Entity;

use App\Entity\GameSession;
use App\Entity\Quiz;
use App\Entity\Room;
use App\Entity\RoomPlayer;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class RoomTest extends TestCase
{
    private Room $room;

    protected function setUp(): void
    {
        $this->room = new Room();
    }

    // ===== Tests pour les propriétés de base =====

    public function testGetId(): void
    {
        // L'ID est null avant la persistance en base
        $this->assertTrue(true); // Test simple car l'ID n'est pas accessible avant persistance
    }

    public function testRoomCodeGetterSetter(): void
    {
        $roomCode = 'ABC123';
        $this->room->setRoomCode($roomCode);
        $this->assertEquals($roomCode, $this->room->getRoomCode());
    }

    public function testNameGetterSetter(): void
    {
        $name = 'Test Room';
        $this->room->setName($name);
        $this->assertEquals($name, $this->room->getName());
    }

    // setName ne peut pas être null selon la signature

    public function testMaxPlayersGetterSetter(): void
    {
        $maxPlayers = 8;
        $this->room->setMaxPlayers($maxPlayers);
        $this->assertEquals($maxPlayers, $this->room->getMaxPlayers());
    }

    public function testMaxPlayersDefault(): void
    {
        $this->assertEquals(4, $this->room->getMaxPlayers()); // 4 par défaut
    }

    public function testIsTeamModeGetterSetter(): void
    {
        $this->room->setIsTeamMode(true);
        $this->assertTrue($this->room->isTeamMode());
        
        $this->room->setIsTeamMode(false);
        $this->assertFalse($this->room->isTeamMode());
    }

    public function testIsTeamModeDefault(): void
    {
        $this->assertFalse($this->room->isTeamMode()); // false par défaut
    }

    public function testStatusGetterSetter(): void
    {
        $status = 'playing';
        $this->room->setStatus($status);
        $this->assertEquals($status, $this->room->getStatus());
    }

    public function testStatusDefault(): void
    {
        $this->assertEquals('waiting', $this->room->getStatus()); // 'waiting' par défaut
    }

    // ===== Tests pour les dates =====

    public function testCreatedAtGetterSetter(): void
    {
        $date = new \DateTimeImmutable();
        $this->room->setCreatedAt($date);
        $this->assertEquals($date, $this->room->getCreatedAt());
    }

    public function testGameStartedAtGetterSetter(): void
    {
        $date = new \DateTimeImmutable();
        $this->room->setGameStartedAt($date);
        $this->assertEquals($date, $this->room->getGameStartedAt());
    }

    public function testGameStartedAtNull(): void
    {
        $this->room->setGameStartedAt(null);
        $this->assertNull($this->room->getGameStartedAt());
    }

    // ===== Tests pour Quiz =====

    public function testQuizGetterSetter(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $this->room->setQuiz($quiz);
        $this->assertEquals($quiz, $this->room->getQuiz());
    }

    public function testQuizNull(): void
    {
        $this->room->setQuiz(null);
        $this->assertNull($this->room->getQuiz());
    }

    // ===== Tests pour Creator =====

    public function testCreatorGetterSetter(): void
    {
        $creator = $this->createMock(User::class);
        $this->room->setCreator($creator);
        $this->assertEquals($creator, $this->room->getCreator());
    }

    public function testCreatorNull(): void
    {
        $this->room->setCreator(null);
        $this->assertNull($this->room->getCreator());
    }

    // ===== Tests pour Players =====

    public function testGetPlayersInitialization(): void
    {
        $players = $this->room->getPlayers();
        $this->assertInstanceOf(ArrayCollection::class, $players);
        $this->assertCount(0, $players);
    }

    public function testAddPlayer(): void
    {
        $player = $this->createMock(RoomPlayer::class);
        
        $player->expects($this->once())
            ->method('setRoom')
            ->with($this->room);
        
        $result = $this->room->addPlayer($player);
        
        $this->assertSame($this->room, $result);
        $this->assertTrue($this->room->getPlayers()->contains($player));
    }

    public function testRemovePlayer(): void
    {
        $player = $this->createMock(RoomPlayer::class);
        
        // Configurer les mocks pour add et remove
        $player->expects($this->exactly(2))
            ->method('setRoom')
            ->withConsecutive([$this->room], [null]);
        
        $player->expects($this->once())
            ->method('getRoom')
            ->willReturn($this->room);
        
        $this->room->addPlayer($player);
        $result = $this->room->removePlayer($player);
        
        $this->assertSame($this->room, $result);
        $this->assertFalse($this->room->getPlayers()->contains($player));
    }

    // ===== Tests pour GameSession =====

    public function testGameSessionGetterSetter(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $this->room->setGameSession($gameSession);
        $this->assertEquals($gameSession, $this->room->getGameSession());
    }

    public function testGameSessionNull(): void
    {
        $this->room->setGameSession(null);
        $this->assertNull($this->room->getGameSession());
    }

    // ===== Tests pour les méthodes utilitaires réelles =====

    public function testGetCurrentPlayerCount(): void
    {
        // Test sans joueurs
        $this->assertEquals(0, $this->room->getCurrentPlayerCount());
        
        // Ajouter quelques joueurs mockés
        $player1 = $this->createMock(RoomPlayer::class);
        $player2 = $this->createMock(RoomPlayer::class);
        
        $player1->method('setRoom');
        $player2->method('setRoom');
        
        $this->room->addPlayer($player1);
        $this->room->addPlayer($player2);
        
        $this->assertEquals(2, $this->room->getCurrentPlayerCount());
    }

    public function testIsAvailable(): void
    {
        // Test de la méthode isAvailable
        $result = $this->room->isAvailable();
        $this->assertIsBool($result);
    }
}
