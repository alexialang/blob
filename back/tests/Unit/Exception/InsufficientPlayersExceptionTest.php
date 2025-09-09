<?php

namespace App\Tests\Unit\Exception;

use App\Exception\InsufficientPlayersException;
use PHPUnit\Framework\TestCase;

class InsufficientPlayersExceptionTest extends TestCase
{
    public function testExceptionCreationWithDefaultMinimum(): void
    {
        $exception = new InsufficientPlayersException();

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Il faut au moins 2 joueurs pour commencer', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionCreationWithCustomMinimum(): void
    {
        $minRequired = 4;
        $exception = new InsufficientPlayersException($minRequired);

        $this->assertEquals("Il faut au moins $minRequired joueurs pour commencer", $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
    }
}
