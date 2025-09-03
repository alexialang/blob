<?php

namespace App\Tests\Entity;

use App\Entity\CategoryQuiz;
use App\Entity\Quiz;
use PHPUnit\Framework\TestCase;

class CategoryQuizTest extends TestCase
{
    private CategoryQuiz $category;

    protected function setUp(): void
    {
        $this->category = new CategoryQuiz();
    }

    public function testCategoryCreation(): void
    {
        $this->assertInstanceOf(CategoryQuiz::class, $this->category);
        $this->assertNull($this->category->getId());
        $this->assertIsCollection($this->category->getQuizs());
    }

    public function testSetAndGetName(): void
    {
        $this->category->setName('Science');

        $this->assertEquals('Science', $this->category->getName());
    }

    public function testNameProperty(): void
    {
        $this->assertNull($this->category->getName());
        
        $this->category->setName('Science');
        $this->assertEquals('Science', $this->category->getName());
    }

    public function testAddAndRemoveQuiz(): void
    {
        $quiz1 = new Quiz();
        $quiz2 = new Quiz();

        $this->category->addQuiz($quiz1);
        $this->category->addQuiz($quiz2);

        $this->assertCount(2, $this->category->getQuizs());
        $this->assertTrue($this->category->getQuizs()->contains($quiz1));
        $this->assertTrue($this->category->getQuizs()->contains($quiz2));
        $this->assertSame($this->category, $quiz1->getCategory());

        $this->category->removeQuiz($quiz1);

        $this->assertCount(1, $this->category->getQuizs());
        $this->assertFalse($this->category->getQuizs()->contains($quiz1));
        $this->assertTrue($this->category->getQuizs()->contains($quiz2));
        $this->assertNull($quiz1->getCategory());
    }

    public function testAddSameQuizTwice(): void
    {
        $quiz = new Quiz();

        $this->category->addQuiz($quiz);
        $this->category->addQuiz($quiz); // Ajout du même

        $this->assertCount(1, $this->category->getQuizs());
    }

    public function testCategoryWithMultipleQuizzes(): void
    {
        $quiz1 = new Quiz();
        $quiz2 = new Quiz();
        $quiz3 = new Quiz();
        
        $this->category->setName('Histoire');
        $this->category->addQuiz($quiz1);
        $this->category->addQuiz($quiz2);
        $this->category->addQuiz($quiz3);

        $this->assertEquals('Histoire', $this->category->getName());
        $this->assertCount(3, $this->category->getQuizs());
    }

    private function assertIsCollection($value): void
    {
        $this->assertTrue(
            is_array($value) || $value instanceof \Traversable,
            'Expected collection (array or Traversable)'
        );
    }
}
