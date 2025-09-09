<?php

namespace App\Tests\Unit\Exception;

use App\Exception\GameSessionNotFoundException;
use PHPUnit\Framework\TestCase;

class GameSessionNotFoundExceptionTest extends TestCase
{
    public function testExceptionCreation(): void
    {
        $gameCode = 'GAME789';
        $exception = new GameSessionNotFoundException($gameCode);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Jeu avec le code '$gameCode' non trouvé", $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
