<?php

namespace App\Tests\Unit\Exception;

use App\Exception\RoomFullException;
use PHPUnit\Framework\TestCase;

class RoomFullExceptionTest extends TestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new RoomFullException();

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Le salon est complet', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
