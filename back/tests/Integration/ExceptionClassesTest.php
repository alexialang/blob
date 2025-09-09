<?php

namespace App\Tests\Integration;

use App\Exception\AnswerAlreadySubmittedException;
use App\Exception\GameNotStartedException;
use App\Exception\InsufficientPlayersException;
use App\Exception\InvalidQuestionException;
use App\Exception\PaymentException;
use App\Exception\PlayerAlreadyInRoomException;
use App\Exception\PlayerNotInRoomException;
use App\Exception\QuizNotFoundException;
use App\Exception\RoomFullException;
use App\Exception\RoomNotFoundException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExceptionClassesTest extends KernelTestCase
{
    public function testPaymentExceptionClass(): void
    {
        $this->assertTrue(class_exists(PaymentException::class));
    }

    public function testQuizNotFoundExceptionClass(): void
    {
        $this->assertTrue(class_exists(QuizNotFoundException::class));
    }

    public function testRoomNotFoundExceptionClass(): void
    {
        $this->assertTrue(class_exists(RoomNotFoundException::class));
    }

    public function testGameNotStartedExceptionClass(): void
    {
        $this->assertTrue(class_exists(GameNotStartedException::class));
    }

    public function testPlayerNotInRoomExceptionClass(): void
    {
        $this->assertTrue(class_exists(PlayerNotInRoomException::class));
    }

    public function testRoomFullExceptionClass(): void
    {
        $this->assertTrue(class_exists(RoomFullException::class));
    }

    public function testInvalidQuestionExceptionClass(): void
    {
        $this->assertTrue(class_exists(InvalidQuestionException::class));
    }

    public function testAnswerAlreadySubmittedExceptionClass(): void
    {
        $this->assertTrue(class_exists(AnswerAlreadySubmittedException::class));
    }

    public function testInsufficientPlayersExceptionClass(): void
    {
        $this->assertTrue(class_exists(InsufficientPlayersException::class));
    }

    public function testPlayerAlreadyInRoomExceptionClass(): void
    {
        $this->assertTrue(class_exists(PlayerAlreadyInRoomException::class));
    }

    public function testExceptionInheritance(): void
    {
        $reflection = new \ReflectionClass(PaymentException::class);
        $this->assertTrue($reflection->isSubclassOf(\Exception::class));
    }

    public function testExceptionCanBeInstantiated(): void
    {
        $exception = new PaymentException('Test message');
        $this->assertInstanceOf(PaymentException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionHasMessage(): void
    {
        $exception = new QuizNotFoundException(123);
        $this->assertStringContainsString('123', $exception->getMessage());
    }

    public function testExceptionHasConstructor(): void
    {
        $reflection = new \ReflectionClass(RoomNotFoundException::class);
        $this->assertTrue($reflection->hasMethod('__construct'));
    }

    public function testAllExceptionsExist(): void
    {
        $exceptions = [
            PaymentException::class,
            QuizNotFoundException::class,
            RoomNotFoundException::class,
            GameNotStartedException::class,
            PlayerNotInRoomException::class,
        ];

        foreach ($exceptions as $exception) {
            $this->assertTrue(class_exists($exception), "Exception $exception should exist");
        }
    }
}
