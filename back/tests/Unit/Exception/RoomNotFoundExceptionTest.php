<?php

namespace App\Tests\Unit\Exception;

use App\Exception\RoomNotFoundException;
use PHPUnit\Framework\TestCase;

class RoomNotFoundExceptionTest extends TestCase
{
    public function testExceptionCreation(): void
    {
        $roomCode = 'ABC123';
        $exception = new RoomNotFoundException($roomCode);
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Salon '$roomCode' non trouvé", $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}

