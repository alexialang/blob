<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Question;
use App\Entity\TypeQuestion;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class TypeQuestionTest extends TestCase
{
    private TypeQuestion $typeQuestion;

    protected function setUp(): void
    {
        $this->typeQuestion = new TypeQuestion();
    }

    public function testGetId(): void
    {
        $this->assertTrue(true);
    }

    public function testNameGetterSetter(): void
    {
        $name = 'Test Type';
        $this->typeQuestion->setName($name);
        $this->assertEquals($name, $this->typeQuestion->getName());
    }

    public function testGetQuestionsInitialization(): void
    {
        $questions = $this->typeQuestion->getQuestions();
        $this->assertInstanceOf(ArrayCollection::class, $questions);
        $this->assertCount(0, $questions);
    }

    public function testAddQuestion(): void
    {
        $question = $this->createMock(Question::class);

        $question->expects($this->once())
            ->method('setTypeQuestion')
            ->with($this->typeQuestion);

        $result = $this->typeQuestion->addQuestion($question);

        $this->assertSame($this->typeQuestion, $result);
        $this->assertTrue($this->typeQuestion->getQuestions()->contains($question));
    }

    public function testRemoveQuestion(): void
    {
        $question = $this->createMock(Question::class);

        $question->expects($this->exactly(2))
            ->method('setTypeQuestion')
            ->withConsecutive([$this->typeQuestion], [null]);

        $question->expects($this->once())
            ->method('getTypeQuestion')
            ->willReturn($this->typeQuestion);

        $this->typeQuestion->addQuestion($question);
        $result = $this->typeQuestion->removeQuestion($question);

        $this->assertSame($this->typeQuestion, $result);
        $this->assertFalse($this->typeQuestion->getQuestions()->contains($question));
    }
}
