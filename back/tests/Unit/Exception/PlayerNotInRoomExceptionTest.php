<?php

namespace App\Tests\Unit\Exception;

use App\Exception\PlayerNotInRoomException;
use PHPUnit\Framework\TestCase;

class PlayerNotInRoomExceptionTest extends TestCase
{
    public function testExceptionCreation(): void
    {
        $roomCode = 'ROOM123';
        $exception = new PlayerNotInRoomException($roomCode);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Vous n'êtes pas dans le salon '$roomCode'", $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
