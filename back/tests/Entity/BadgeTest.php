<?php

namespace App\Tests\Entity;

use App\Entity\Badge;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BadgeTest extends TestCase
{
    private Badge $badge;
    private User $user;

    protected function setUp(): void
    {
        $this->badge = new Badge();
        $this->user = new User();
    }

    private function setEntityId($entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }

    public function testBadgeCreation(): void
    {
        $this->assertInstanceOf(Badge::class, $this->badge);
    }

    public function testSetAndGetId(): void
    {
        $this->setEntityId($this->badge, 1);
        $this->assertEquals(1, $this->badge->getId());
    }

    public function testSetAndGetName(): void
    {
        $name = 'Test Badge';
        $this->badge->setName($name);
        $this->assertEquals($name, $this->badge->getName());
    }

    public function testSetAndGetDescription(): void
    {
        $description = 'This is a test badge';
        $this->badge->setDescription($description);
        $this->assertEquals($description, $this->badge->getDescription());
    }

    public function testSetAndGetImage(): void
    {
        $image = 'badge-test.svg';
        $this->badge->setImage($image);
        $this->assertEquals($image, $this->badge->getImage());
    }

    public function testAddAndRemoveUser(): void
    {
        $this->setEntityId($this->user, 1);
        
        $this->badge->addUser($this->user);
        
        $this->assertCount(1, $this->badge->getUsers());
        $this->assertTrue($this->badge->getUsers()->contains($this->user));
        
        $this->badge->removeUser($this->user);
        
        $this->assertCount(0, $this->badge->getUsers());
        $this->assertFalse($this->badge->getUsers()->contains($this->user));
    }

    public function testAddSameUserTwice(): void
    {
        $this->setEntityId($this->user, 1);
        
        $this->badge->addUser($this->user);
        $this->badge->addUser($this->user); // Ajouter deux fois
        
        $this->assertCount(1, $this->badge->getUsers()); // Doit rester à 1
    }

    public function testRemoveNonExistentUser(): void
    {
        $this->setEntityId($this->user, 1);
        
        $this->badge->removeUser($this->user);
        
        $this->assertCount(0, $this->badge->getUsers());
    }

    public function testBadgeWithAllProperties(): void
    {
        $this->setEntityId($this->badge, 1);
        $this->badge->setName('Complete Badge');
        $this->badge->setDescription('A complete test badge');
        $this->badge->setImage('complete-badge.svg');
        
        $this->assertEquals(1, $this->badge->getId());
        $this->assertEquals('Complete Badge', $this->badge->getName());
        $this->assertEquals('A complete test badge', $this->badge->getDescription());
        $this->assertEquals('complete-badge.svg', $this->badge->getImage());
    }

    public function testMultipleUsers(): void
    {
        $user1 = new User();
        $this->setEntityId($user1, 1);
        
        $user2 = new User();
        $this->setEntityId($user2, 2);
        
        $this->badge->addUser($user1);
        $this->badge->addUser($user2);
        
        $this->assertCount(2, $this->badge->getUsers());
        $this->assertTrue($this->badge->getUsers()->contains($user1));
        $this->assertTrue($this->badge->getUsers()->contains($user2));
    }
}
