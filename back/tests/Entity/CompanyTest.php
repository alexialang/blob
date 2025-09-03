<?php

namespace App\Tests\Entity;

use App\Entity\Company;
use App\Entity\User;
use App\Entity\Quiz;
use App\Entity\Group;
use PHPUnit\Framework\TestCase;

class CompanyTest extends TestCase
{
    private Company $company;

    protected function setUp(): void
    {
        $this->company = new Company();
    }

    public function testCompanyCreation(): void
    {
        $this->assertInstanceOf(Company::class, $this->company);
        $this->assertNull($this->company->getId());
        $this->assertIsCollection($this->company->getUsers());
        $this->assertIsCollection($this->company->getQuizs());
        $this->assertIsCollection($this->company->getGroups());
    }

    public function testSetAndGetBasicProperties(): void
    {
        $dateCreation = new \DateTime();
        
        $this->company->setName('Test Company');
        $this->company->setDateCreation($dateCreation);

        $this->assertEquals('Test Company', $this->company->getName());
        $this->assertEquals($dateCreation, $this->company->getDateCreation());
    }

    public function testAddAndRemoveUser(): void
    {
        $user1 = new User();
        $user2 = new User();

        $this->company->addUser($user1);
        $this->company->addUser($user2);

        $this->assertCount(2, $this->company->getUsers());
        $this->assertTrue($this->company->getUsers()->contains($user1));
        $this->assertTrue($this->company->getUsers()->contains($user2));
        $this->assertSame($this->company, $user1->getCompany());
        $this->assertSame($this->company, $user2->getCompany());

        $this->company->removeUser($user1);

        $this->assertCount(1, $this->company->getUsers());
        $this->assertFalse($this->company->getUsers()->contains($user1));
        $this->assertTrue($this->company->getUsers()->contains($user2));
        $this->assertNull($user1->getCompany());
    }

    public function testAddSameUserTwice(): void
    {
        $user = new User();

        $this->company->addUser($user);
        $this->company->addUser($user); // Ajout du même

        $this->assertCount(1, $this->company->getUsers());
    }

    public function testAddAndRemoveQuiz(): void
    {
        $quiz1 = new Quiz();
        $quiz2 = new Quiz();

        $this->company->addQuiz($quiz1);
        $this->company->addQuiz($quiz2);

        $this->assertCount(2, $this->company->getQuizs());
        $this->assertTrue($this->company->getQuizs()->contains($quiz1));
        $this->assertTrue($this->company->getQuizs()->contains($quiz2));
        $this->assertSame($this->company, $quiz1->getCompany());

        $this->company->removeQuiz($quiz1);

        $this->assertCount(1, $this->company->getQuizs());
        $this->assertFalse($this->company->getQuizs()->contains($quiz1));
        $this->assertTrue($this->company->getQuizs()->contains($quiz2));
        $this->assertNull($quiz1->getCompany());
    }

    public function testAddSameQuizTwice(): void
    {
        $quiz = new Quiz();

        $this->company->addQuiz($quiz);
        $this->company->addQuiz($quiz); // Ajout du même

        $this->assertCount(1, $this->company->getQuizs());
    }

    public function testAddAndRemoveGroup(): void
    {
        $group1 = new Group();
        $group2 = new Group();

        $this->company->addGroup($group1);
        $this->company->addGroup($group2);

        $this->assertCount(2, $this->company->getGroups());
        $this->assertTrue($this->company->getGroups()->contains($group1));
        $this->assertTrue($this->company->getGroups()->contains($group2));
        $this->assertSame($this->company, $group1->getCompany());

        $this->company->removeGroup($group1);

        $this->assertCount(1, $this->company->getGroups());
        $this->assertFalse($this->company->getGroups()->contains($group1));
        $this->assertTrue($this->company->getGroups()->contains($group2));
        $this->assertNull($group1->getCompany());
    }

    private function assertIsCollection($value): void
    {
        $this->assertTrue(
            is_array($value) || $value instanceof \Traversable,
            'Expected collection (array or Traversable)'
        );
    }
}
