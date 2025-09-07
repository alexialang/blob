<?php

namespace App\Tests\Unit\Exception;

use App\Exception\GameNotStartedException;
use PHPUnit\Framework\TestCase;

class GameNotStartedExceptionTest extends TestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new GameNotStartedException();
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Le jeu n'est pas en cours", $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}

