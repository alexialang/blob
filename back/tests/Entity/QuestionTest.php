<?php

namespace App\Tests\Entity;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\Answer;
use App\Entity\TypeQuestion;
use App\Enum\Difficulty;
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    private Question $question;

    protected function setUp(): void
    {
        $this->question = new Question();
    }

    public function testQuestionCreation(): void
    {
        $this->assertInstanceOf(Question::class, $this->question);
        $this->assertNull($this->question->getId());
        $this->assertIsCollection($this->question->getAnswers());
    }

    public function testSetAndGetBasicProperties(): void
    {
        $typeQuestion = new TypeQuestion();
        
        $this->question->setQuestion('Quelle est la capitale de la France ?');
        $this->question->setTypeQuestion($typeQuestion);
        $this->question->setDifficulty(Difficulty::EASY);

        $this->assertEquals('Quelle est la capitale de la France ?', $this->question->getQuestion());
        $this->assertSame($typeQuestion, $this->question->getTypeQuestion());
        $this->assertEquals(Difficulty::EASY, $this->question->getDifficulty());
    }

    public function testSetAndGetQuiz(): void
    {
        $quiz = new Quiz();
        $quiz->setTitle('Quiz Géographie');

        $this->question->setQuiz($quiz);

        $this->assertSame($quiz, $this->question->getQuiz());
    }

    public function testAddAndRemoveAnswer(): void
    {
        $answer1 = new Answer();
        $answer2 = new Answer();

        $this->question->addAnswer($answer1);
        $this->question->addAnswer($answer2);

        $this->assertCount(2, $this->question->getAnswers());
        $this->assertTrue($this->question->getAnswers()->contains($answer1));
        $this->assertTrue($this->question->getAnswers()->contains($answer2));
        $this->assertSame($this->question, $answer1->getQuestion());

        $this->question->removeAnswer($answer1);

        $this->assertCount(1, $this->question->getAnswers());
        $this->assertFalse($this->question->getAnswers()->contains($answer1));
        $this->assertTrue($this->question->getAnswers()->contains($answer2));
        $this->assertNull($answer1->getQuestion());
    }

    public function testAddSameAnswerTwice(): void
    {
        $answer = new Answer();

        $this->question->addAnswer($answer);
        $this->question->addAnswer($answer); // Ajout du même

        $this->assertCount(1, $this->question->getAnswers());
    }

    public function testQuestionWithAnswersAndQuiz(): void
    {
        $quiz = new Quiz();
        $typeQuestion = new TypeQuestion();
        $answer1 = new Answer();
        $answer2 = new Answer();
        $answer3 = new Answer();
        
        $this->question->setQuestion('Question complète');
        $this->question->setTypeQuestion($typeQuestion);
        $this->question->setDifficulty(Difficulty::MEDIUM);
        $this->question->setQuiz($quiz);
        $this->question->addAnswer($answer1);
        $this->question->addAnswer($answer2);
        $this->question->addAnswer($answer3);

        $this->assertEquals('Question complète', $this->question->getQuestion());
        $this->assertSame($typeQuestion, $this->question->getTypeQuestion());
        $this->assertEquals(Difficulty::MEDIUM, $this->question->getDifficulty());
        $this->assertSame($quiz, $this->question->getQuiz());
        $this->assertCount(3, $this->question->getAnswers());
    }

    private function assertIsCollection($value): void
    {
        $this->assertTrue(
            is_array($value) || $value instanceof \Traversable,
            'Expected collection (array or Traversable)'
        );
    }
}
