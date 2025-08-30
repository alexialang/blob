<?php

namespace App\Tests\Entity;

use App\Entity\UserPermission;
use App\Entity\User;
use App\Enum\Permission as PermissionEnum;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class UserPermissionTest extends TestCase
{
    private UserPermission $userPermission;
    private User $user;

    protected function setUp(): void
    {
        $this->userPermission = new UserPermission();
        $this->user = new User();
    }

    private function setEntityId($entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }

    public function testUserPermissionCreation(): void
    {
        $this->assertInstanceOf(UserPermission::class, $this->userPermission);
    }

    public function testSetAndGetId(): void
    {
        $this->setEntityId($this->userPermission, 1);
        $this->assertEquals(1, $this->userPermission->getId());
    }

    public function testSetAndGetUser(): void
    {
        $this->setEntityId($this->user, 1);
        $this->userPermission->setUser($this->user);
        
        $this->assertSame($this->user, $this->userPermission->getUser());
    }

    public function testSetAndGetPermission(): void
    {
        $this->userPermission->setPermission(PermissionEnum::MANAGE_USERS);
        
        $this->assertSame(PermissionEnum::MANAGE_USERS, $this->userPermission->getPermission());
    }

    public function testPermissionEnumValues(): void
    {
        $this->userPermission->setPermission(PermissionEnum::MANAGE_USERS);
        $this->assertEquals(PermissionEnum::MANAGE_USERS, $this->userPermission->getPermission());
        
        $this->userPermission->setPermission(PermissionEnum::VIEW_RESULTS);
        $this->assertEquals(PermissionEnum::VIEW_RESULTS, $this->userPermission->getPermission());
        
        $this->userPermission->setPermission(PermissionEnum::CREATE_QUIZ);
        $this->assertEquals(PermissionEnum::CREATE_QUIZ, $this->userPermission->getPermission());
    }

    public function testUserPermissionWithAllProperties(): void
    {
        $this->setEntityId($this->userPermission, 1);
        $this->setEntityId($this->user, 1);
        
        $this->userPermission->setUser($this->user);
        $this->userPermission->setPermission(PermissionEnum::VIEW_RESULTS);
        
        $this->assertEquals(1, $this->userPermission->getId());
        $this->assertSame($this->user, $this->userPermission->getUser());
        $this->assertSame(PermissionEnum::VIEW_RESULTS, $this->userPermission->getPermission());
    }
}
