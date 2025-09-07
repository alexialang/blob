<?php

namespace App\Tests\Unit\Entity;

use App\Entity\GameSession;
use App\Entity\Room;
use PHPUnit\Framework\TestCase;

class GameSessionTest extends TestCase
{
    private GameSession $gameSession;

    protected function setUp(): void
    {
        $this->gameSession = new GameSession();
    }

    public function testConstructor(): void
    {
        $gameSession = new GameSession();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $gameSession->getStartedAt());
        $this->assertNotNull($gameSession->getGameCode());
        $this->assertStringStartsWith('game_', $gameSession->getGameCode());
        $this->assertEquals([], $gameSession->getSharedScores());
        $this->assertEquals('playing', $gameSession->getStatus());
        $this->assertEquals(0, $gameSession->getCurrentQuestionIndex());
        $this->assertEquals(30, $gameSession->getCurrentQuestionDuration());
    }

    public function testGetId(): void
    {
        // ID is only set after persistence, so initially it returns null
        // We need to use reflection to test this properly
        $reflection = new \ReflectionClass($this->gameSession);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->gameSession, 123);
        
        $this->assertEquals(123, $this->gameSession->getId());
    }

    public function testGameCode(): void
    {
        $gameCode = 'test_game_123';
        $this->gameSession->setGameCode($gameCode);
        
        $this->assertEquals($gameCode, $this->gameSession->getGameCode());
    }

    public function testRoom(): void
    {
        $room = $this->createMock(Room::class);
        $this->gameSession->setRoom($room);
        
        $this->assertSame($room, $this->gameSession->getRoom());
        
        $this->gameSession->setRoom(null);
        $this->assertNull($this->gameSession->getRoom());
    }

    public function testStatus(): void
    {
        $status = 'finished';
        $this->gameSession->setStatus($status);
        
        $this->assertEquals($status, $this->gameSession->getStatus());
    }

    public function testCurrentQuestionIndex(): void
    {
        $index = 5;
        $this->gameSession->setCurrentQuestionIndex($index);
        
        $this->assertEquals($index, $this->gameSession->getCurrentQuestionIndex());
    }

    public function testStartedAt(): void
    {
        $startedAt = new \DateTimeImmutable('2023-01-01 10:00:00');
        $this->gameSession->setStartedAt($startedAt);
        
        $this->assertSame($startedAt, $this->gameSession->getStartedAt());
    }

    public function testFinishedAt(): void
    {
        $finishedAt = new \DateTimeImmutable('2023-01-01 11:00:00');
        $this->gameSession->setFinishedAt($finishedAt);
        
        $this->assertSame($finishedAt, $this->gameSession->getFinishedAt());
        
        $this->gameSession->setFinishedAt(null);
        $this->assertNull($this->gameSession->getFinishedAt());
    }

    public function testSharedScores(): void
    {
        $scores = ['user1' => 100, 'user2' => 85];
        $this->gameSession->setSharedScores($scores);
        
        $this->assertEquals($scores, $this->gameSession->getSharedScores());
        
        $this->gameSession->setSharedScores(null);
        $this->assertNull($this->gameSession->getSharedScores());
    }

    public function testCurrentQuestionStartedAt(): void
    {
        $startedAt = new \DateTimeImmutable('2023-01-01 10:15:00');
        $this->gameSession->setCurrentQuestionStartedAt($startedAt);
        
        $this->assertSame($startedAt, $this->gameSession->getCurrentQuestionStartedAt());
    }

    public function testCurrentQuestionDuration(): void
    {
        $duration = 45;
        $this->gameSession->setCurrentQuestionDuration($duration);
        
        $this->assertEquals($duration, $this->gameSession->getCurrentQuestionDuration());
    }
}
