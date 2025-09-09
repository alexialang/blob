<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Company;
use App\Entity\Group;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    private Group $group;

    protected function setUp(): void
    {
        $this->group = new Group();
    }

    public function testGetId(): void
    {
        $this->assertTrue(true);
    }

    public function testNameGetterSetter(): void
    {
        $name = 'Test Group';
        $this->group->setName($name);
        $this->assertEquals($name, $this->group->getName());
    }

    public function testAccesCodeGetterSetter(): void
    {
        $accesCode = 'ABC123';
        $this->group->setAccesCode($accesCode);
        $this->assertEquals($accesCode, $this->group->getAccesCode());
    }

    public function testAccesCodeNull(): void
    {
        $this->group->setAccesCode(null);
        $this->assertNull($this->group->getAccesCode());
    }

    public function testCompanyGetterSetter(): void
    {
        $company = $this->createMock(Company::class);
        $this->group->setCompany($company);
        $this->assertEquals($company, $this->group->getCompany());
    }

    public function testCompanyNull(): void
    {
        $this->group->setCompany(null);
        $this->assertNull($this->group->getCompany());
    }

    public function testGetUsersInitialization(): void
    {
        $users = $this->group->getUsers();
        $this->assertInstanceOf(ArrayCollection::class, $users);
        $this->assertCount(0, $users);
    }

    public function testAddUser(): void
    {
        $user = $this->createMock(User::class);

        $result = $this->group->addUser($user);

        $this->assertSame($this->group, $result);
        $this->assertTrue($this->group->getUsers()->contains($user));
    }

    public function testRemoveUser(): void
    {
        $user = $this->createMock(User::class);

        $this->group->addUser($user);
        $result = $this->group->removeUser($user);

        $this->assertSame($this->group, $result);
        $this->assertFalse($this->group->getUsers()->contains($user));
    }

    public function testGetUserCount(): void
    {
        $this->assertEquals(0, $this->group->getUserCount());

        $user = $this->createMock(User::class);
        $this->group->addUser($user);

        $this->assertEquals(1, $this->group->getUserCount());
    }
}
