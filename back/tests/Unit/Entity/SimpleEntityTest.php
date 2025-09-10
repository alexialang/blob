<?php

namespace App\Tests\Unit\Entity;

use App\Entity\CategoryQuiz;
use App\Entity\Quiz;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class SimpleEntityTest extends TestCase
{
    public function testUserEntity(): void
    {
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testQuizEntity(): void
    {
        $quiz = new Quiz();
        $this->assertInstanceOf(Quiz::class, $quiz);
    }

    public function testCategoryQuizEntity(): void
    {
        $category = new CategoryQuiz();
        $this->assertInstanceOf(CategoryQuiz::class, $category);
    }
}



