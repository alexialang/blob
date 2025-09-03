<?php

namespace App\Tests\Entity;

use App\Entity\Group;
use App\Entity\Company;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    private Group $group;

    protected function setUp(): void
    {
        $this->group = new Group();
    }

    public function testGroupCreation(): void
    {
        $this->assertInstanceOf(Group::class, $this->group);
        $this->assertNull($this->group->getId());
        $this->assertIsCollection($this->group->getUsers());
    }

    public function testSetAndGetName(): void
    {
        $this->group->setName('Équipe Marketing');

        $this->assertEquals('Équipe Marketing', $this->group->getName());
    }

    public function testSetAndGetCompany(): void
    {
        $company = new Company();
        $company->setName('Test Company');

        $this->group->setCompany($company);

        $this->assertSame($company, $this->group->getCompany());
    }

    public function testAddAndRemoveUser(): void
    {
        $user1 = new User();
        $user2 = new User();

        $this->group->addUser($user1);
        $this->group->addUser($user2);

        $this->assertCount(2, $this->group->getUsers());
        $this->assertTrue($this->group->getUsers()->contains($user1));
        $this->assertTrue($this->group->getUsers()->contains($user2));

        $this->group->removeUser($user1);

        $this->assertCount(1, $this->group->getUsers());
        $this->assertFalse($this->group->getUsers()->contains($user1));
        $this->assertTrue($this->group->getUsers()->contains($user2));
    }

    public function testSetAndGetAccessCode(): void
    {
        $this->group->setAccesCode('ABC123');

        $this->assertEquals('ABC123', $this->group->getAccesCode());
    }

    private function assertIsCollection($value): void
    {
        $this->assertTrue(
            is_array($value) || $value instanceof \Traversable,
            'Expected collection (array or Traversable)'
        );
    }
}
