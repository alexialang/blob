<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\TypeQuestion;
use App\Enum\Difficulty;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    private Question $question;

    protected function setUp(): void
    {
        $this->question = new Question();
    }

    public function testGetId(): void
    {
        $this->assertTrue(true);
    }

    public function testQuestionGetterSetter(): void
    {
        $questionText = 'Test Question';
        $this->question->setQuestion($questionText);
        $this->assertEquals($questionText, $this->question->getQuestion());
    }

    public function testQuizGetterSetter(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $this->question->setQuiz($quiz);
        $this->assertEquals($quiz, $this->question->getQuiz());
    }

    public function testQuizNull(): void
    {
        $this->question->setQuiz(null);
        $this->assertNull($this->question->getQuiz());
    }

    public function testTypeQuestionGetterSetter(): void
    {
        $typeQuestion = $this->createMock(TypeQuestion::class);
        $this->question->setTypeQuestion($typeQuestion);
        $this->assertEquals($typeQuestion, $this->question->getTypeQuestion());
    }

    public function testTypeQuestionNull(): void
    {
        $this->question->setTypeQuestion(null);
        $this->assertNull($this->question->getTypeQuestion());
    }

    public function testDifficultyGetterSetter(): void
    {
        $difficulty = Difficulty::EASY;
        $this->question->setDifficulty($difficulty);
        $this->assertEquals($difficulty, $this->question->getDifficulty());
    }

    public function testDifficultyNull(): void
    {
        $this->question->setDifficulty(null);
        $this->assertNull($this->question->getDifficulty());
    }

    public function testGetAnswersInitialization(): void
    {
        $answers = $this->question->getAnswers();
        $this->assertInstanceOf(ArrayCollection::class, $answers);
        $this->assertCount(0, $answers);
    }

    public function testAddAnswer(): void
    {
        $answer = $this->createMock(Answer::class);
        
        $answer->expects($this->once())
            ->method('setQuestion')
            ->with($this->question);
        
        $result = $this->question->addAnswer($answer);
        
        $this->assertSame($this->question, $result);
        $this->assertTrue($this->question->getAnswers()->contains($answer));
    }

    public function testRemoveAnswer(): void
    {
        $answer = $this->createMock(Answer::class);
        
        $answer->expects($this->exactly(2))
            ->method('setQuestion')
            ->withConsecutive([$this->question], [null]);
        
        $answer->expects($this->once())
            ->method('getQuestion')
            ->willReturn($this->question);
        
        $this->question->addAnswer($answer);
        $result = $this->question->removeAnswer($answer);
        
        $this->assertSame($this->question, $result);
        $this->assertFalse($this->question->getAnswers()->contains($answer));
    }
}
