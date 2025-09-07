<?php

namespace App\Tests\Unit\Event;

use App\Entity\Quiz;
use App\Entity\User;
use App\Event\QuizCreatedEvent;
use PHPUnit\Framework\TestCase;

class QuizCreatedEventTest extends TestCase
{
    public function testEventConstant(): void
    {
        $this->assertEquals('quiz.created', QuizCreatedEvent::NAME);
    }

    public function testEventCreation(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $user = $this->createMock(User::class);
        
        $event = new QuizCreatedEvent($quiz, $user);
        
        $this->assertSame($quiz, $event->getQuiz());
        $this->assertSame($user, $event->getUser());
        $this->assertInstanceOf('Symfony\Contracts\EventDispatcher\Event', $event);
    }
}

