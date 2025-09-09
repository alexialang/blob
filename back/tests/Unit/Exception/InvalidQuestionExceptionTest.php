<?php

namespace App\Tests\Unit\Exception;

use App\Exception\InvalidQuestionException;
use PHPUnit\Framework\TestCase;

class InvalidQuestionExceptionTest extends TestCase
{
    public function testExceptionCreationWithDefaultReason(): void
    {
        $questionId = 123;
        $exception = new InvalidQuestionException($questionId);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals("Question invalide pour la question ID $questionId", $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionCreationWithCustomReason(): void
    {
        $questionId = 456;
        $reason = 'Question supprimée';
        $exception = new InvalidQuestionException($questionId, $reason);

        $this->assertEquals("$reason pour la question ID $questionId", $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
    }
}
