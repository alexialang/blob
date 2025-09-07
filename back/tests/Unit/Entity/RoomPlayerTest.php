<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Room;
use App\Entity\RoomPlayer;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class RoomPlayerTest extends TestCase
{
    private RoomPlayer $roomPlayer;

    protected function setUp(): void
    {
        $this->roomPlayer = new RoomPlayer();
    }

    public function testId(): void
    {
        $reflection = new \ReflectionClass($this->roomPlayer);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->roomPlayer, 123);

        $this->assertEquals(123, $this->roomPlayer->getId());
    }

    public function testRoom(): void
    {
        $room = $this->createMock(Room::class);
        
        $result = $this->roomPlayer->setRoom($room);
        
        $this->assertSame($this->roomPlayer, $result);
        $this->assertSame($room, $this->roomPlayer->getRoom());
    }

    public function testUser(): void
    {
        $user = $this->createMock(User::class);
        
        $result = $this->roomPlayer->setUser($user);
        
        $this->assertSame($this->roomPlayer, $result);
        $this->assertSame($user, $this->roomPlayer->getUser());
    }

    public function testIsReady(): void
    {
        $this->assertFalse($this->roomPlayer->isReady());
        
        $result = $this->roomPlayer->setIsReady(true);
        
        $this->assertSame($this->roomPlayer, $result);
        $this->assertTrue($this->roomPlayer->isReady());
    }

    public function testIsCreator(): void
    {
        $this->assertFalse($this->roomPlayer->isCreator());
        
        $result = $this->roomPlayer->setIsCreator(true);
        
        $this->assertSame($this->roomPlayer, $result);
        $this->assertTrue($this->roomPlayer->isCreator());
    }

    public function testTeam(): void
    {
        $this->assertNull($this->roomPlayer->getTeam());
        
        $result = $this->roomPlayer->setTeam('Team A');
        
        $this->assertSame($this->roomPlayer, $result);
        $this->assertEquals('Team A', $this->roomPlayer->getTeam());
    }

    public function testJoinedAt(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->roomPlayer->getJoinedAt());
        
        $date = new \DateTimeImmutable('2023-01-01 10:00:00');
        $result = $this->roomPlayer->setJoinedAt($date);
        
        $this->assertSame($this->roomPlayer, $result);
        $this->assertSame($date, $this->roomPlayer->getJoinedAt());
    }

    public function testConstructor(): void
    {
        $roomPlayer = new RoomPlayer();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $roomPlayer->getJoinedAt());
        $this->assertFalse($roomPlayer->isReady());
        $this->assertFalse($roomPlayer->isCreator());
        $this->assertNull($roomPlayer->getTeam());
    }
}