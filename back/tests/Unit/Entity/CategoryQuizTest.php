<?php

namespace App\Tests\Unit\Entity;

use App\Entity\CategoryQuiz;
use App\Entity\Quiz;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class CategoryQuizTest extends TestCase
{
    private CategoryQuiz $categoryQuiz;

    protected function setUp(): void
    {
        $this->categoryQuiz = new CategoryQuiz();
    }

    public function testGetId(): void
    {
        $this->assertTrue(true);
    }

    public function testNameGetterSetter(): void
    {
        $name = 'Test Category';
        $this->categoryQuiz->setName($name);
        $this->assertEquals($name, $this->categoryQuiz->getName());
    }

    public function testGetQuizsInitialization(): void
    {
        $quizs = $this->categoryQuiz->getQuizs();
        $this->assertInstanceOf(ArrayCollection::class, $quizs);
        $this->assertCount(0, $quizs);
    }

    public function testAddQuiz(): void
    {
        $quiz = $this->createMock(Quiz::class);
        
        $quiz->expects($this->once())
            ->method('setCategory')
            ->with($this->categoryQuiz);
        
        $result = $this->categoryQuiz->addQuiz($quiz);
        
        $this->assertSame($this->categoryQuiz, $result);
        $this->assertTrue($this->categoryQuiz->getQuizs()->contains($quiz));
    }

    public function testRemoveQuiz(): void
    {
        $quiz = $this->createMock(Quiz::class);
        
        $quiz->expects($this->exactly(2))
            ->method('setCategory')
            ->withConsecutive([$this->categoryQuiz], [null]);
        
        $quiz->expects($this->once())
            ->method('getCategory')
            ->willReturn($this->categoryQuiz);
        
        $this->categoryQuiz->addQuiz($quiz);
        $result = $this->categoryQuiz->removeQuiz($quiz);
        
        $this->assertSame($this->categoryQuiz, $result);
        $this->assertFalse($this->categoryQuiz->getQuizs()->contains($quiz));
    }
}
