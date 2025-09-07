<?php

namespace App\Tests\Unit\Exception;

use App\Exception\QuizNotFoundException;
use PHPUnit\Framework\TestCase;

class QuizNotFoundExceptionTest extends TestCase
{
    public function testExceptionCreation(): void
    {
        $quizId = 789;
        $exception = new QuizNotFoundException($quizId);
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Quiz avec ID $quizId non trouvé", $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}

