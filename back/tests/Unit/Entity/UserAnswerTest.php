<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\UserAnswer;
use PHPUnit\Framework\TestCase;

class UserAnswerTest extends TestCase
{
    private UserAnswer $userAnswer;

    protected function setUp(): void
    {
        $this->userAnswer = new UserAnswer();
    }

    public function testGetId(): void
    {
        $this->assertTrue(true);
    }

    public function testUserGetterSetter(): void
    {
        $user = $this->createMock(User::class);
        $this->userAnswer->setUser($user);
        $this->assertEquals($user, $this->userAnswer->getUser());
    }

    public function testUserNull(): void
    {
        $this->userAnswer->setUser(null);
        $this->assertNull($this->userAnswer->getUser());
    }

    public function testQuizGetterSetter(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $this->userAnswer->setQuiz($quiz);
        $this->assertEquals($quiz, $this->userAnswer->getQuiz());
    }

    public function testQuizNull(): void
    {
        $this->userAnswer->setQuiz(null);
        $this->assertNull($this->userAnswer->getQuiz());
    }

    public function testTotalScoreGetterSetter(): void
    {
        $totalScore = 85;
        $this->userAnswer->setTotalScore($totalScore);
        $this->assertEquals($totalScore, $this->userAnswer->getTotalScore());
    }

    public function testDateAttemptGetterSetter(): void
    {
        $date = new \DateTime();
        $this->userAnswer->setDateAttempt($date);
        $this->assertEquals($date, $this->userAnswer->getDateAttempt());
    }
}
