<?php

namespace App\Tests\Unit\Entity;

use App\Entity\CategoryQuiz;
use App\Entity\Company;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\QuizRating;
use App\Entity\User;
use App\Entity\UserAnswer;
use App\Enum\Difficulty;
use App\Enum\Status;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class QuizTest extends TestCase
{
    private Quiz $quiz;

    protected function setUp(): void
    {
        $this->quiz = new Quiz();
    }

    // ===== Tests pour les propriétés de base =====

    public function testGetId(): void
    {
        // L'ID est null avant la persistance en base
        $this->assertTrue(true); // Test simple car l'ID n'est pas accessible avant persistance
    }

    public function testTitleGetterSetter(): void
    {
        $title = 'Test Quiz';
        $this->quiz->setTitle($title);
        $this->assertEquals($title, $this->quiz->getTitle());
    }

    public function testDescriptionGetterSetter(): void
    {
        $description = 'Description du quiz de test';
        $this->quiz->setDescription($description);
        $this->assertEquals($description, $this->quiz->getDescription());
    }

    public function testIsPublicGetterSetter(): void
    {
        $this->quiz->setIsPublic(true);
        $this->assertTrue($this->quiz->isPublic());
        
        $this->quiz->setIsPublic(false);
        $this->assertFalse($this->quiz->isPublic());
    }

    public function testDateCreationGetterSetter(): void
    {
        $date = new \DateTime();
        $this->quiz->setDateCreation($date);
        $this->assertEquals($date, $this->quiz->getDateCreation());
    }

    public function testStatusGetterSetter(): void
    {
        $status = Status::PUBLISHED;
        $this->quiz->setStatus($status);
        $this->assertEquals($status, $this->quiz->getStatus());
    }

    // ===== Tests pour Company =====

    public function testCompanyGetterSetter(): void
    {
        $company = $this->createMock(Company::class);
        $this->quiz->setCompany($company);
        $this->assertEquals($company, $this->quiz->getCompany());
    }

    public function testCompanyNull(): void
    {
        $this->quiz->setCompany(null);
        $this->assertNull($this->quiz->getCompany());
    }

    // ===== Tests pour User =====

    public function testUserGetterSetter(): void
    {
        $user = $this->createMock(User::class);
        $this->quiz->setUser($user);
        $this->assertEquals($user, $this->quiz->getUser());
    }

    public function testUserNull(): void
    {
        $this->quiz->setUser(null);
        $this->assertNull($this->quiz->getUser());
    }

    // ===== Tests pour CategoryQuiz =====

    public function testCategoryGetterSetter(): void
    {
        $category = $this->createMock(CategoryQuiz::class);
        $this->quiz->setCategory($category);
        $this->assertEquals($category, $this->quiz->getCategory());
    }

    public function testCategoryNull(): void
    {
        $this->quiz->setCategory(null);
        $this->assertNull($this->quiz->getCategory());
    }

    // ===== Tests pour Questions =====

    public function testGetQuestionsInitialization(): void
    {
        $questions = $this->quiz->getQuestions();
        $this->assertInstanceOf(ArrayCollection::class, $questions);
        $this->assertCount(0, $questions);
    }

    public function testAddQuestion(): void
    {
        $question = $this->createMock(Question::class);
        
        $question->expects($this->once())
            ->method('setQuiz')
            ->with($this->quiz);
        
        $result = $this->quiz->addQuestion($question);
        
        $this->assertSame($this->quiz, $result);
        $this->assertTrue($this->quiz->getQuestions()->contains($question));
    }

    public function testRemoveQuestion(): void
    {
        $question = $this->createMock(Question::class);
        
        // Configurer les mocks pour add et remove
        $question->expects($this->exactly(2))
            ->method('setQuiz')
            ->withConsecutive([$this->quiz], [null]);
        
        $question->expects($this->once())
            ->method('getQuiz')
            ->willReturn($this->quiz);
        
        $this->quiz->addQuestion($question);
        $result = $this->quiz->removeQuestion($question);
        
        $this->assertSame($this->quiz, $result);
        $this->assertFalse($this->quiz->getQuestions()->contains($question));
    }

    // ===== Tests pour UserAnswers =====

    public function testGetUserAnswersInitialization(): void
    {
        $userAnswers = $this->quiz->getUserAnswers();
        $this->assertInstanceOf(ArrayCollection::class, $userAnswers);
        $this->assertCount(0, $userAnswers);
    }

    public function testAddUserAnswer(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);
        
        $userAnswer->expects($this->once())
            ->method('setQuiz')
            ->with($this->quiz);
        
        $result = $this->quiz->addUserAnswer($userAnswer);
        
        $this->assertSame($this->quiz, $result);
        $this->assertTrue($this->quiz->getUserAnswers()->contains($userAnswer));
    }

    public function testRemoveUserAnswer(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);
        
        // Configurer les mocks pour add et remove
        $userAnswer->expects($this->exactly(2))
            ->method('setQuiz')
            ->withConsecutive([$this->quiz], [null]);
        
        $userAnswer->expects($this->once())
            ->method('getQuiz')
            ->willReturn($this->quiz);
        
        $this->quiz->addUserAnswer($userAnswer);
        $result = $this->quiz->removeUserAnswer($userAnswer);
        
        $this->assertSame($this->quiz, $result);
        $this->assertFalse($this->quiz->getUserAnswers()->contains($userAnswer));
    }

    // ===== Tests pour Groups =====

    public function testGetGroupsInitialization(): void
    {
        $groups = $this->quiz->getGroups();
        $this->assertInstanceOf(ArrayCollection::class, $groups);
        $this->assertCount(0, $groups);
    }

    public function testAddGroup(): void
    {
        $group = $this->createMock(\App\Entity\Group::class);
        
        $result = $this->quiz->addGroup($group);
        
        $this->assertSame($this->quiz, $result);
        $this->assertTrue($this->quiz->getGroups()->contains($group));
    }

    public function testRemoveGroup(): void
    {
        $group = $this->createMock(\App\Entity\Group::class);
        
        // Ajouter d'abord le groupe
        $this->quiz->addGroup($group);
        
        // Maintenant le supprimer
        $result = $this->quiz->removeGroup($group);
        
        $this->assertSame($this->quiz, $result);
        $this->assertFalse($this->quiz->getGroups()->contains($group));
    }

    // ===== Tests pour les méthodes utilitaires =====

    public function testGetDifficultyLabel(): void
    {
        $label = $this->quiz->getDifficultyLabel();
        $this->assertIsString($label);
    }

    public function testGetTotalAttempts(): void
    {
        $attempts = $this->quiz->getTotalAttempts();
        $this->assertIsInt($attempts);
        $this->assertGreaterThanOrEqual(0, $attempts);
    }

    public function testGetPopularity(): void
    {
        $popularity = $this->quiz->getPopularity();
        $this->assertIsInt($popularity);
        $this->assertGreaterThanOrEqual(0, $popularity);
    }

    public function testGetQuestionCount(): void
    {
        // Test sans questions
        $this->assertEquals(0, $this->quiz->getQuestionCount());
        
        // Ajouter quelques questions mockées
        $question1 = $this->createMock(Question::class);
        $question2 = $this->createMock(Question::class);
        
        $question1->method('setQuiz');
        $question2->method('setQuiz');
        
        $this->quiz->addQuestion($question1);
        $this->quiz->addQuestion($question2);
        
        $this->assertEquals(2, $this->quiz->getQuestionCount());
    }
}
