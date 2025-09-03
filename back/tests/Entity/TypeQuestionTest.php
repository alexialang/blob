<?php

namespace App\Tests\Entity;

use App\Entity\TypeQuestion;
use App\Entity\Question;
use PHPUnit\Framework\TestCase;

class TypeQuestionTest extends TestCase
{
    private TypeQuestion $typeQuestion;

    protected function setUp(): void
    {
        $this->typeQuestion = new TypeQuestion();
    }

    public function testTypeQuestionCreation(): void
    {
        $this->assertInstanceOf(TypeQuestion::class, $this->typeQuestion);
        $this->assertNull($this->typeQuestion->getId());
        $this->assertIsCollection($this->typeQuestion->getQuestions());
    }

    public function testSetAndGetName(): void
    {
        $this->typeQuestion->setName('Multiple Choice');

        $this->assertEquals('Multiple Choice', $this->typeQuestion->getName());
    }

    public function testAddAndRemoveQuestion(): void
    {
        $question1 = new Question();
        $question2 = new Question();

        $this->typeQuestion->addQuestion($question1);
        $this->typeQuestion->addQuestion($question2);

        $this->assertCount(2, $this->typeQuestion->getQuestions());
        $this->assertTrue($this->typeQuestion->getQuestions()->contains($question1));
        $this->assertTrue($this->typeQuestion->getQuestions()->contains($question2));
        $this->assertSame($this->typeQuestion, $question1->getTypeQuestion());

        $this->typeQuestion->removeQuestion($question1);

        $this->assertCount(1, $this->typeQuestion->getQuestions());
        $this->assertFalse($this->typeQuestion->getQuestions()->contains($question1));
        $this->assertTrue($this->typeQuestion->getQuestions()->contains($question2));
        $this->assertNull($question1->getTypeQuestion());
    }

    public function testAddSameQuestionTwice(): void
    {
        $question = new Question();

        $this->typeQuestion->addQuestion($question);
        $this->typeQuestion->addQuestion($question); // Ajout du même

        $this->assertCount(1, $this->typeQuestion->getQuestions());
    }

    private function assertIsCollection($value): void
    {
        $this->assertTrue(
            is_array($value) || $value instanceof \Traversable,
            'Expected collection (array or Traversable)'
        );
    }
}

