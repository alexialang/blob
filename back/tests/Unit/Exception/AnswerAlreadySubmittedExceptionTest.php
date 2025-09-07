<?php

namespace App\Tests\Unit\Exception;

use App\Exception\AnswerAlreadySubmittedException;
use PHPUnit\Framework\TestCase;

class AnswerAlreadySubmittedExceptionTest extends TestCase
{
    public function testExceptionCreation(): void
    {
        $questionId = 42;
        $exception = new AnswerAlreadySubmittedException($questionId);
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Réponse déjà soumise pour la question ID $questionId", $exception->getMessage());
        $this->assertEquals(409, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}

