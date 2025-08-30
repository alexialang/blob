<?php

namespace App\Tests\Entity;

use App\Entity\Answer;
use App\Entity\Question;
use PHPUnit\Framework\TestCase;

class AnswerTest extends TestCase
{
    private Answer $answer;

    protected function setUp(): void
    {
        $this->answer = new Answer();
    }

    public function testAnswerCreation(): void
    {
        $this->assertInstanceOf(Answer::class, $this->answer);
        $this->assertNull($this->answer->getId());
        $this->assertNull($this->answer->isCorrect());
    }

    public function testSetAndGetBasicProperties(): void
    {
        $this->answer->setAnswer('Paris');
        $this->answer->setIsCorrect(true);

        $this->assertEquals('Paris', $this->answer->getAnswer());
        $this->assertTrue($this->answer->isCorrect());
    }

    public function testSetAndGetQuestion(): void
    {
        $question = new Question();
        $question->setQuestion('Quelle est la capitale de la France ?');

        $this->answer->setQuestion($question);

        $this->assertSame($question, $this->answer->getQuestion());
    }

    public function testCorrectProperty(): void
    {
        $this->assertNull($this->answer->isCorrect());

        $this->answer->setIsCorrect(true);
        $this->assertTrue($this->answer->isCorrect());

        $this->answer->setIsCorrect(false);
        $this->assertFalse($this->answer->isCorrect());
    }

    public function testCompleteAnswer(): void
    {
        $question = new Question();
        $question->setQuestion('Question test');
        
        $this->answer->setAnswer('Réponse correcte');
        $this->answer->setIsCorrect(true);
        $this->answer->setQuestion($question);

        $this->assertEquals('Réponse correcte', $this->answer->getAnswer());
        $this->assertTrue($this->answer->isCorrect());
        $this->assertSame($question, $this->answer->getQuestion());
    }

    public function testMultipleAnswersForSameQuestion(): void
    {
        $question = new Question();
        $answer1 = new Answer();
        $answer2 = new Answer();
        
        $answer1->setAnswer('Bonne réponse');
        $answer1->setIsCorrect(true);
        $answer1->setQuestion($question);
        
        $answer2->setAnswer('Mauvaise réponse');
        $answer2->setIsCorrect(false);
        $answer2->setQuestion($question);

        $this->assertSame($question, $answer1->getQuestion());
        $this->assertSame($question, $answer2->getQuestion());
        $this->assertTrue($answer1->isCorrect());
        $this->assertFalse($answer2->isCorrect());
    }
}
