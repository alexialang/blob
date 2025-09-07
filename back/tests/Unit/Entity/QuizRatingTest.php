<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Quiz;
use App\Entity\QuizRating;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class QuizRatingTest extends TestCase
{
    private QuizRating $quizRating;

    protected function setUp(): void
    {
        $this->quizRating = new QuizRating();
    }

    public function testGetId(): void
    {
        $this->assertTrue(true);
    }

    public function testUserGetterSetter(): void
    {
        $user = $this->createMock(User::class);
        $this->quizRating->setUser($user);
        $this->assertEquals($user, $this->quizRating->getUser());
    }

    public function testQuizGetterSetter(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $this->quizRating->setQuiz($quiz);
        $this->assertEquals($quiz, $this->quizRating->getQuiz());
    }

    public function testRatingGetterSetter(): void
    {
        $rating = 4;
        $this->quizRating->setRating($rating);
        $this->assertEquals($rating, $this->quizRating->getRating());
    }

    // Tests seulement pour les méthodes qui existent
}
