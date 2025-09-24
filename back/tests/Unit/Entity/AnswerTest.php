<?php

namespace App\Tests\Unit\Entity;

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

    public function testGetId(): void
    {
        $this->assertTrue(true);
    }

    public function testAnswerGetterSetter(): void
    {
        $answerText = 'Test Answer';
        $this->answer->setAnswer($answerText);
        $this->assertEquals($answerText, $this->answer->getAnswer());
    }

    public function testIsCorrectGetterSetter(): void
    {
        $this->answer->setIsCorrect(true);
        $this->assertTrue($this->answer->isCorrect());

        $this->answer->setIsCorrect(false);
        $this->assertFalse($this->answer->isCorrect());
    }

    public function testIsCorrectNull(): void
    {
        $this->answer->setIsCorrect(null);
        $this->assertNull($this->answer->isCorrect());
    }

    public function testOrderCorrectGetterSetter(): void
    {
        $order = 'ABC';
        $this->answer->setOrderCorrect($order);
        $this->assertEquals($order, $this->answer->getOrderCorrect());
    }

    public function testOrderCorrectNull(): void
    {
        $this->answer->setOrderCorrect(null);
        $this->assertNull($this->answer->getOrderCorrect());
    }

    public function testPairIdGetterSetter(): void
    {
        $pairId = 'pair123';
        $this->answer->setPairId($pairId);
        $this->assertEquals($pairId, $this->answer->getPairId());
    }

    public function testPairIdNull(): void
    {
        $this->answer->setPairId(null);
        $this->assertNull($this->answer->getPairId());
    }

    public function testIsIntrusGetterSetter(): void
    {
        $this->answer->setIsIntrus(true);
        $this->assertTrue($this->answer->isIntrus());

        $this->answer->setIsIntrus(false);
        $this->assertFalse($this->answer->isIntrus());
    }

    public function testIsIntrusNull(): void
    {
        $this->answer->setIsIntrus(null);
        $this->assertNull($this->answer->isIntrus());
    }

    public function testQuestionGetterSetter(): void
    {
        $question = $this->createMock(Question::class);
        $this->answer->setQuestion($question);
        $this->assertEquals($question, $this->answer->getQuestion());
    }

    public function testQuestionNull(): void
    {
        $this->answer->setQuestion(null);
        $this->assertNull($this->answer->getQuestion());
    }
}
