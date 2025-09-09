<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Badge;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class BadgeTest extends TestCase
{
    private Badge $badge;

    protected function setUp(): void
    {
        $this->badge = new Badge();
    }

    public function testId(): void
    {
        $reflection = new \ReflectionClass($this->badge);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->badge, 123);

        $this->assertEquals(123, $this->badge->getId());
    }

    public function testName(): void
    {
        $this->assertNull($this->badge->getName());

        $result = $this->badge->setName('Premier Quiz');

        $this->assertSame($this->badge, $result);
        $this->assertEquals('Premier Quiz', $this->badge->getName());
    }

    public function testDescription(): void
    {
        $this->assertNull($this->badge->getDescription());

        $result = $this->badge->setDescription('Félicitations pour votre premier quiz !');

        $this->assertSame($this->badge, $result);
        $this->assertEquals('Félicitations pour votre premier quiz !', $this->badge->getDescription());
    }

    public function testImage(): void
    {
        $this->assertNull($this->badge->getImage());

        $result = $this->badge->setImage('badge-first-quiz.png');

        $this->assertSame($this->badge, $result);
        $this->assertEquals('badge-first-quiz.png', $this->badge->getImage());
    }

    public function testUsers(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->badge->getUsers());
        $this->assertCount(0, $this->badge->getUsers());
    }

    public function testAddUser(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('addBadge')
            ->with($this->badge);

        $result = $this->badge->addUser($user);

        $this->assertSame($this->badge, $result);
        $this->assertTrue($this->badge->getUsers()->contains($user));
        $this->assertCount(1, $this->badge->getUsers());
    }

    public function testAddUserAlreadyExists(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('addBadge')
            ->with($this->badge);

        // Ajout initial
        $this->badge->addUser($user);

        // Deuxième ajout du même utilisateur
        $result = $this->badge->addUser($user);

        $this->assertSame($this->badge, $result);
        $this->assertCount(1, $this->badge->getUsers()); // Toujours 1, pas de doublon
    }

    public function testRemoveUser(): void
    {
        $user = $this->createMock(User::class);

        // Mock pour l'ajout
        $user->expects($this->once())
            ->method('addBadge')
            ->with($this->badge);

        // Mock pour la suppression
        $user->expects($this->once())
            ->method('removeBadge')
            ->with($this->badge);

        // Ajout puis suppression
        $this->badge->addUser($user);
        $result = $this->badge->removeUser($user);

        $this->assertSame($this->badge, $result);
        $this->assertFalse($this->badge->getUsers()->contains($user));
        $this->assertCount(0, $this->badge->getUsers());
    }

    public function testRemoveUserNotExists(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->never())
            ->method('removeBadge');

        $result = $this->badge->removeUser($user);

        $this->assertSame($this->badge, $result);
        $this->assertCount(0, $this->badge->getUsers());
    }

    public function testConstructor(): void
    {
        $badge = new Badge();

        $this->assertInstanceOf(ArrayCollection::class, $badge->getUsers());
        $this->assertCount(0, $badge->getUsers());
        $this->assertNull($badge->getName());
        $this->assertNull($badge->getDescription());
        $this->assertNull($badge->getImage());
    }

    public function testFluentInterface(): void
    {
        $result = $this->badge
            ->setName('Test Badge')
            ->setDescription('Test Description')
            ->setImage('test.png');

        $this->assertSame($this->badge, $result);
        $this->assertEquals('Test Badge', $this->badge->getName());
        $this->assertEquals('Test Description', $this->badge->getDescription());
        $this->assertEquals('test.png', $this->badge->getImage());
    }
}
