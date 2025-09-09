<?php

namespace App\Tests\Integration;

use App\Entity\Answer;
use App\Entity\Badge;
use App\Entity\CategoryQuiz;
use App\Entity\Company;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\Room;
use App\Entity\RoomPlayer;
use App\Entity\User;
use App\Entity\UserAnswer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityClassesTest extends KernelTestCase
{
    public function testUserEntityExists(): void
    {
        $this->assertTrue(class_exists(User::class));
    }

    public function testQuizEntityExists(): void
    {
        $this->assertTrue(class_exists(Quiz::class));
    }

    public function testBadgeEntityExists(): void
    {
        $this->assertTrue(class_exists(Badge::class));
    }

    public function testCategoryQuizEntityExists(): void
    {
        $this->assertTrue(class_exists(CategoryQuiz::class));
    }

    public function testCompanyEntityExists(): void
    {
        $this->assertTrue(class_exists(Company::class));
    }

    public function testQuestionEntityExists(): void
    {
        $this->assertTrue(class_exists(Question::class));
    }

    public function testAnswerEntityExists(): void
    {
        $this->assertTrue(class_exists(Answer::class));
    }

    public function testRoomEntityExists(): void
    {
        $this->assertTrue(class_exists(Room::class));
    }

    public function testRoomPlayerEntityExists(): void
    {
        $this->assertTrue(class_exists(RoomPlayer::class));
    }

    public function testUserAnswerEntityExists(): void
    {
        $this->assertTrue(class_exists(UserAnswer::class));
    }

    public function testUserEntityCanBeInstantiated(): void
    {
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testQuizEntityCanBeInstantiated(): void
    {
        $quiz = new Quiz();
        $this->assertInstanceOf(Quiz::class, $quiz);
    }

    public function testBadgeEntityCanBeInstantiated(): void
    {
        $badge = new Badge();
        $this->assertInstanceOf(Badge::class, $badge);
    }

    public function testCategoryQuizEntityCanBeInstantiated(): void
    {
        $category = new CategoryQuiz();
        $this->assertInstanceOf(CategoryQuiz::class, $category);
    }

    public function testCompanyEntityCanBeInstantiated(): void
    {
        $company = new Company();
        $this->assertInstanceOf(Company::class, $company);
    }
}
