<?php

namespace App\Tests\Unit\Exception;

use App\Exception\UnauthorizedGameActionException;
use PHPUnit\Framework\TestCase;

class UnauthorizedGameActionExceptionTest extends TestCase
{
    public function testExceptionCreationWithDefaultAction(): void
    {
        $exception = new UnauthorizedGameActionException();
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Seul le créateur peut effectuer cette action", $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
    
    public function testExceptionCreationWithCustomAction(): void
    {
        $action = 'suppression';
        $exception = new UnauthorizedGameActionException($action);
        
        $this->assertEquals("Seul le créateur peut effectuer cette $action", $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
    }
}

