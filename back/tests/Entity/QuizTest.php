<?php

namespace App\Tests\Entity;

use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\CategoryQuiz;
use App\Entity\Question;
use App\Enum\Status;
use PHPUnit\Framework\TestCase;

class QuizTest extends TestCase
{
    private Quiz $quiz;

    protected function setUp(): void
    {
        $this->quiz = new Quiz();
    }

    public function testQuizCreation(): void
    {
        $this->assertInstanceOf(Quiz::class, $this->quiz);
        $this->assertNull($this->quiz->getId());
        $this->assertIsCollection($this->quiz->getQuestions());
        $this->assertIsCollection($this->quiz->getUserAnswers());
    }

    public function testSetAndGetBasicProperties(): void
    {
        $dateCreation = new \DateTime();
        
        $this->quiz->setTitle('Test Quiz');
        $this->quiz->setDescription('Description du quiz');
        $this->quiz->setStatus(Status::PUBLISHED);
        $this->quiz->setIsPublic(true);
        $this->quiz->setDateCreation($dateCreation);

        $this->assertEquals('Test Quiz', $this->quiz->getTitle());
        $this->assertEquals('Description du quiz', $this->quiz->getDescription());
        $this->assertEquals(Status::PUBLISHED, $this->quiz->getStatus());
        $this->assertTrue($this->quiz->isPublic());
        $this->assertEquals($dateCreation, $this->quiz->getDateCreation());
    }

    public function testSetAndGetUser(): void
    {
        $user = new User();
        $user->setFirstName('John');

        $this->quiz->setUser($user);

        $this->assertSame($user, $this->quiz->getUser());
    }

    public function testSetAndGetCategory(): void
    {
        $category = new CategoryQuiz();
        $category->setName('Test Category');

        $this->quiz->setCategory($category);

        $this->assertSame($category, $this->quiz->getCategory());
    }

    public function testPublicProperty(): void
    {
        $this->quiz->setIsPublic(false);

        $this->assertFalse($this->quiz->isPublic());
        
        $this->quiz->setIsPublic(true);
        
        $this->assertTrue($this->quiz->isPublic());
    }

    public function testAddAndRemoveQuestion(): void
    {
        $question1 = new Question();
        $question2 = new Question();

        $this->quiz->addQuestion($question1);
        $this->quiz->addQuestion($question2);

        $this->assertCount(2, $this->quiz->getQuestions());
        $this->assertTrue($this->quiz->getQuestions()->contains($question1));
        $this->assertTrue($this->quiz->getQuestions()->contains($question2));

        $this->quiz->removeQuestion($question1);

        $this->assertCount(1, $this->quiz->getQuestions());
        $this->assertFalse($this->quiz->getQuestions()->contains($question1));
        $this->assertTrue($this->quiz->getQuestions()->contains($question2));
    }

    public function testAddSameQuestionTwice(): void
    {
        $question = new Question();

        $this->quiz->addQuestion($question);
        $this->quiz->addQuestion($question); // Ajout du même

        $this->assertCount(1, $this->quiz->getQuestions());
    }

    public function testGettersReturnCorrectTypes(): void
    {
        $this->assertIsCollection($this->quiz->getQuestions());
        $this->assertIsCollection($this->quiz->getUserAnswers());
        $this->assertIsCollection($this->quiz->getGroups());
    }

    public function testQuizStatistics(): void
    {
        $this->assertEquals(0, $this->quiz->getTotalAttempts());
        $this->assertEquals(1, $this->quiz->getPopularity()); // Min = 1
        $this->assertEquals(0, $this->quiz->getQuestionCount());
    }

    private function assertIsCollection($value): void
    {
        $this->assertTrue(
            is_array($value) || $value instanceof \Traversable,
            'Expected collection (array or Traversable)'
        );
    }
}
