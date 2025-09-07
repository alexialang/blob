<?php

namespace App\Tests\Unit\Exception;

use App\Exception\PlayerAlreadyInRoomException;
use PHPUnit\Framework\TestCase;

class PlayerAlreadyInRoomExceptionTest extends TestCase
{
    public function testExceptionCreation(): void
    {
        $roomCode = 'ROOM456';
        $exception = new PlayerAlreadyInRoomException($roomCode);
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Vous êtes déjà dans le salon '$roomCode'", $exception->getMessage());
        $this->assertEquals(409, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}

