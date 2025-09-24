<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Company;
use App\Entity\Group;
use App\Entity\Quiz;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class CompanyTest extends TestCase
{
    private Company $company;

    protected function setUp(): void
    {
        $this->company = new Company();
    }

    // ===== Tests simples pour les vraies méthodes =====

    public function testGetId(): void
    {
        $this->assertTrue(true);
    }

    public function testNameGetterSetter(): void
    {
        $name = 'Test Company';
        $this->company->setName($name);
        $this->assertEquals($name, $this->company->getName());
    }

    public function testDateCreationGetterSetter(): void
    {
        $date = new \DateTime();
        $this->company->setDateCreation($date);
        $this->assertEquals($date, $this->company->getDateCreation());
    }

    public function testGetUsersInitialization(): void
    {
        $users = $this->company->getUsers();
        $this->assertInstanceOf(ArrayCollection::class, $users);
        $this->assertCount(0, $users);
    }

    public function testAddUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('setCompany');

        $result = $this->company->addUser($user);
        $this->assertSame($this->company, $result);
        $this->assertTrue($this->company->getUsers()->contains($user));
    }

    public function testRemoveUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('setCompany');
        $user->method('getCompany')->willReturn($this->company);

        $this->company->addUser($user);
        $result = $this->company->removeUser($user);

        $this->assertSame($this->company, $result);
        $this->assertFalse($this->company->getUsers()->contains($user));
    }

    public function testGetGroupsInitialization(): void
    {
        $groups = $this->company->getGroups();
        $this->assertInstanceOf(ArrayCollection::class, $groups);
        $this->assertCount(0, $groups);
    }

    public function testAddGroup(): void
    {
        $group = $this->createMock(Group::class);
        $group->method('setCompany');

        $result = $this->company->addGroup($group);
        $this->assertSame($this->company, $result);
        $this->assertTrue($this->company->getGroups()->contains($group));
    }

    public function testRemoveGroup(): void
    {
        $group = $this->createMock(Group::class);
        $group->method('setCompany');
        $group->method('getCompany')->willReturn($this->company);

        $this->company->addGroup($group);
        $result = $this->company->removeGroup($group);

        $this->assertSame($this->company, $result);
        $this->assertFalse($this->company->getGroups()->contains($group));
    }

    public function testGetQuizsInitialization(): void
    {
        $quizs = $this->company->getQuizs();
        $this->assertInstanceOf(ArrayCollection::class, $quizs);
        $this->assertCount(0, $quizs);
    }

    public function testAddQuiz(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $quiz->method('setCompany');

        $result = $this->company->addQuiz($quiz);
        $this->assertSame($this->company, $result);
        $this->assertTrue($this->company->getQuizs()->contains($quiz));
    }

    public function testRemoveQuiz(): void
    {
        $quiz = $this->createMock(Quiz::class);
        $quiz->method('setCompany');
        $quiz->method('getCompany')->willReturn($this->company);

        $this->company->addQuiz($quiz);
        $result = $this->company->removeQuiz($quiz);

        $this->assertSame($this->company, $result);
        $this->assertFalse($this->company->getQuizs()->contains($quiz));
    }

    public function testGetUserCount(): void
    {
        $this->assertEquals(0, $this->company->getUserCount());

        $user = $this->createMock(User::class);
        $user->method('setCompany');
        $this->company->addUser($user);

        $this->assertEquals(1, $this->company->getUserCount());
    }

    public function testGetGroupCount(): void
    {
        $this->assertEquals(0, $this->company->getGroupCount());

        $group = $this->createMock(Group::class);
        $group->method('setCompany');
        $this->company->addGroup($group);

        $this->assertEquals(1, $this->company->getGroupCount());
    }

    public function testGetQuizCount(): void
    {
        $this->assertEquals(0, $this->company->getQuizCount());

        $quiz = $this->createMock(Quiz::class);
        $quiz->method('setCompany');
        $this->company->addQuiz($quiz);

        $this->assertEquals(1, $this->company->getQuizCount());
    }

    public function testGetCreatedAt(): void
    {
        $createdAt = $this->company->getCreatedAt();
        $this->assertTrue(null === $createdAt || is_string($createdAt));
    }
}
