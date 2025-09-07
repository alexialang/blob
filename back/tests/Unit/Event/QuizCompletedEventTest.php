<?php

namespace App\Tests\Unit\Event;

use App\Entity\User;
use App\Entity\UserAnswer;
use App\Event\QuizCompletedEvent;
use PHPUnit\Framework\TestCase;

class QuizCompletedEventTest extends TestCase
{
    public function testEventConstant(): void
    {
        $this->assertEquals('quiz.completed', QuizCompletedEvent::NAME);
    }

    public function testEventCreation(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);
        $user = $this->createMock(User::class);
        
        $event = new QuizCompletedEvent($userAnswer, $user);
        
        $this->assertSame($userAnswer, $event->getUserAnswer());
        $this->assertSame($user, $event->getUser());
        $this->assertInstanceOf('Symfony\Contracts\EventDispatcher\Event', $event);
    }

    public function testGetScore(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);
        $user = $this->createMock(User::class);
        $userAnswer->method('getTotalScore')->willReturn(85);
        
        $event = new QuizCompletedEvent($userAnswer, $user);
        
        $this->assertEquals(85, $event->getScore());
    }

    public function testGetScoreNull(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);
        $user = $this->createMock(User::class);
        $userAnswer->method('getTotalScore')->willReturn(null);
        
        $event = new QuizCompletedEvent($userAnswer, $user);
        
        $this->assertNull($event->getScore());
    }
}

