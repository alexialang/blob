<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\UserPermission;
use App\Enum\Permission;
use PHPUnit\Framework\TestCase;

class UserPermissionTest extends TestCase
{
    private UserPermission $userPermission;

    protected function setUp(): void
    {
        $this->userPermission = new UserPermission();
    }

    public function testGetId(): void
    {
        // Set a value via reflection first
        $reflection = new \ReflectionClass($this->userPermission);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->userPermission, 123);
        
        $this->assertEquals(123, $this->userPermission->getId());
    }

    public function testUserGetterSetter(): void
    {
        $user = $this->createMock(User::class);
        $this->userPermission->setUser($user);
        $this->assertEquals($user, $this->userPermission->getUser());
        
        // Test fluent interface
        $result = $this->userPermission->setUser($user);
        $this->assertSame($this->userPermission, $result);
    }

    public function testPermissionGetterSetter(): void
    {
        $permission = Permission::CREATE_QUIZ;
        $this->userPermission->setPermission($permission);
        $this->assertEquals($permission, $this->userPermission->getPermission());
        
        // Test all permission types
        $this->userPermission->setPermission(Permission::MANAGE_USERS);
        $this->assertEquals(Permission::MANAGE_USERS, $this->userPermission->getPermission());
        
        $this->userPermission->setPermission(Permission::VIEW_RESULTS);
        $this->assertEquals(Permission::VIEW_RESULTS, $this->userPermission->getPermission());
        
        // Test fluent interface
        $result = $this->userPermission->setPermission($permission);
        $this->assertSame($this->userPermission, $result);
    }
    
    public function testInitialState(): void
    {
        $permission = new UserPermission();
        $this->assertNull($permission->getUser());
        $this->assertNull($permission->getPermission());
    }
    
    public function testObjectInstantiation(): void
    {
        $permission = new UserPermission();
        $this->assertInstanceOf(UserPermission::class, $permission);
    }
    
    public function testCompletePermissionFlow(): void
    {
        $user = $this->createMock(User::class);
        $permission = Permission::CREATE_QUIZ;
        
        // Set both properties
        $this->userPermission->setUser($user);
        $this->userPermission->setPermission($permission);
        
        // Verify they're correctly set
        $this->assertSame($user, $this->userPermission->getUser());
        $this->assertSame($permission, $this->userPermission->getPermission());
    }
}
