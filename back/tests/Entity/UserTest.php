<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Company;
use App\Entity\Group;
use App\Entity\Badge;
use App\Enum\Permission;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testUserCreation(): void
    {
        $this->assertInstanceOf(User::class, $this->user);
        $this->assertNull($this->user->getId());
        $this->assertTrue($this->user->isActive());
        $this->assertFalse($this->user->isVerified());
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    public function testSetAndGetBasicProperties(): void
    {
        $this->user->setFirstName('John');
        $this->user->setLastName('Doe');
        $this->user->setEmail('john.doe@example.com');
        $this->user->setPseudo('johndoe');

        $this->assertEquals('John', $this->user->getFirstName());
        $this->assertEquals('Doe', $this->user->getLastName());
        $this->assertEquals('john.doe@example.com', $this->user->getEmail());
        $this->assertEquals('johndoe', $this->user->getPseudo());
        $this->assertEquals('john.doe@example.com', $this->user->getUserIdentifier());
    }

    public function testIsAdmin(): void
    {
        $this->assertFalse($this->user->isAdmin());

        $this->user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        $this->assertTrue($this->user->isAdmin());
    }

    public function testRolesManagement(): void
    {
        $roles = ['ROLE_ADMIN', 'ROLE_MODERATOR'];

        $this->user->setRoles($roles);

        $userRoles = $this->user->getRoles();
        $this->assertContains('ROLE_USER', $userRoles); // Toujours ajouté automatiquement
        $this->assertContains('ROLE_ADMIN', $userRoles);
        $this->assertContains('ROLE_MODERATOR', $userRoles);
    }

    public function testCompanyRelation(): void
    {
        $company = new Company();
        $company->setName('Test Company');

        $this->user->setCompany($company);

        $this->assertEquals($company, $this->user->getCompany());
        $this->assertEquals('Test Company', $this->user->getCompanyName());
        $this->assertEquals($company->getId(), $this->user->getCompanyId());
    }

    public function testGroupsManagement(): void
    {
        $group1 = new Group();
        $group1->setName('Group 1');
        
        $group2 = new Group();
        $group2->setName('Group 2');

        $this->user->addGroup($group1);
        $this->user->addGroup($group2);

        $this->assertCount(2, $this->user->getGroups());
        $this->assertTrue($this->user->getGroups()->contains($group1));
        $this->assertTrue($this->user->getGroups()->contains($group2));

        $this->user->removeGroup($group1);

        $this->assertCount(1, $this->user->getGroups());
        $this->assertFalse($this->user->getGroups()->contains($group1));
        $this->assertTrue($this->user->getGroups()->contains($group2));
    }

    public function testBadgesManagement(): void
    {
        $badge1 = new Badge();
        $badge1->setName('First Quiz');
        
        $badge2 = new Badge();
        $badge2->setName('Expert');

        $this->user->addBadge($badge1);
        $this->user->addBadge($badge2);

        $this->assertCount(2, $this->user->getBadges());
        $this->assertTrue($this->user->getBadges()->contains($badge1));
        $this->assertTrue($this->user->getBadges()->contains($badge2));

        $this->user->addBadge($badge1);
        $this->assertCount(2, $this->user->getBadges());

        $this->user->removeBadge($badge1);

        $this->assertCount(1, $this->user->getBadges());
        $this->assertFalse($this->user->getBadges()->contains($badge1));
    }

    public function testAvatarData(): void
    {
        $avatarData = json_encode([
            'shape' => 'circle',
            'color' => '#FF5733'
        ]);

        $this->user->setAvatar($avatarData);

        $this->assertEquals($avatarData, $this->user->getAvatar());
        $this->assertEquals('circle', $this->user->getAvatarShape());
        $this->assertEquals('#FF5733', $this->user->getAvatarColor());
    }

    public function testAvatarDataWhenNull(): void
    {
        $this->assertNull($this->user->getAvatar());
        $this->assertNull($this->user->getAvatarShape());
        $this->assertNull($this->user->getAvatarColor());
    }

    public function testDatesManagement(): void
    {
        $registrationDate = new \DateTimeImmutable('2023-01-01');
        $lastAccess = new \DateTime('2023-12-31');

        $this->user->setDateRegistration($registrationDate);
        $this->user->setLastAccess($lastAccess);

        $this->assertEquals($registrationDate, $this->user->getDateRegistration());
        $this->assertEquals($lastAccess, $this->user->getLastAccess());
    }

    public function testUserActivation(): void
    {
        $this->assertTrue($this->user->isActive());

        $this->user->setIsActive(false);

        $this->assertFalse($this->user->isActive());
    }

    public function testPasswordResetToken(): void
    {
        $token = 'reset-token-123';
        $requestTime = new \DateTimeImmutable();

        $this->user->setPasswordResetToken($token);
        $this->user->setPasswordResetRequestAt($requestTime);

        $this->assertEquals($token, $this->user->getPasswordResetToken());
        $this->assertEquals($requestTime, $this->user->getPasswordResetRequestAt());
    }
}

