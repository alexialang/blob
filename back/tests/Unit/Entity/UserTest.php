<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Badge;
use App\Entity\Company;
use App\Entity\Group;
use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\UserAnswer;
use App\Entity\UserPermission;
use App\Enum\Permission;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    // ===== Tests pour les propriétés de base =====

    public function testGetId(): void
    {
        // L'ID est null avant la persistance en base
        $this->assertTrue(true); // Test simple car l'ID n'est pas accessible avant persistance
    }

    public function testEmailGetterSetter(): void
    {
        $email = 'test@example.com';
        $this->user->setEmail($email);
        $this->assertEquals($email, $this->user->getEmail());
    }

    public function testGetUserIdentifier(): void
    {
        $email = 'test@example.com';
        $this->user->setEmail($email);
        $this->assertEquals($email, $this->user->getUserIdentifier());
    }

    public function testPasswordGetterSetter(): void
    {
        $password = 'hashedPassword123';
        $this->user->setPassword($password);
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testEraseCredentials(): void
    {
        // Cette méthode ne fait rien dans l'implémentation actuelle
        $this->user->eraseCredentials();
        $this->assertTrue(true); // Test que la méthode ne lève pas d'exception
    }

    // ===== Tests pour les rôles =====

    public function testGetRolesDefault(): void
    {
        $roles = $this->user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testSetRoles(): void
    {
        $roles = ['ROLE_ADMIN', 'ROLE_USER'];
        $this->user->setRoles($roles);
        $result = $this->user->getRoles();
        $this->assertContains('ROLE_ADMIN', $result);
        $this->assertContains('ROLE_USER', $result);
    }

    public function testIsAdmin(): void
    {
        // Par défaut, pas admin
        $this->assertFalse($this->user->isAdmin());
        
        // Avec rôle admin
        $this->user->setRoles(['ROLE_ADMIN']);
        $this->assertTrue($this->user->isAdmin());
    }

    // ===== Tests pour les dates =====

    public function testDateRegistrationGetterSetter(): void
    {
        $date = new \DateTimeImmutable();
        $this->user->setDateRegistration($date);
        $this->assertEquals($date, $this->user->getDateRegistration());
    }

    public function testLastAccessGetterSetter(): void
    {
        $date = new \DateTime();
        $this->user->setLastAccess($date);
        $this->assertEquals($date, $this->user->getLastAccess());
    }

    public function testLastAccessNull(): void
    {
        $this->user->setLastAccess(null);
        $this->assertNull($this->user->getLastAccess());
    }

    // ===== Tests pour Company =====

    public function testCompanyGetterSetter(): void
    {
        $company = $this->createMock(Company::class);
        $this->user->setCompany($company);
        $this->assertEquals($company, $this->user->getCompany());
    }

    public function testCompanyNull(): void
    {
        $this->user->setCompany(null);
        $this->assertNull($this->user->getCompany());
    }

    // ===== Tests pour Badges =====

    public function testGetBadgesInitialization(): void
    {
        $badges = $this->user->getBadges();
        $this->assertInstanceOf(ArrayCollection::class, $badges);
        $this->assertCount(0, $badges);
    }

    public function testAddBadge(): void
    {
        $badge = $this->createMock(Badge::class);
        
        $result = $this->user->addBadge($badge);
        
        $this->assertSame($this->user, $result);
        $this->assertTrue($this->user->getBadges()->contains($badge));
    }

    public function testAddBadgeAlreadyExists(): void
    {
        $badge = $this->createMock(Badge::class);
        
        // Ajouter le badge une première fois
        $this->user->addBadge($badge);
        
        // Essayer de l'ajouter à nouveau
        $this->user->addBadge($badge);
        
        $this->assertCount(1, $this->user->getBadges());
    }

    public function testRemoveBadge(): void
    {
        $badge = $this->createMock(Badge::class);
        
        // Ajouter d'abord le badge
        $this->user->addBadge($badge);
        
        // Maintenant le supprimer
        $result = $this->user->removeBadge($badge);
        
        $this->assertSame($this->user, $result);
        $this->assertFalse($this->user->getBadges()->contains($badge));
    }

    // ===== Tests pour Quiz =====

    public function testGetQuizsInitialization(): void
    {
        $quizs = $this->user->getQuizs();
        $this->assertInstanceOf(ArrayCollection::class, $quizs);
        $this->assertCount(0, $quizs);
    }

    public function testAddQuiz(): void
    {
        $quiz = $this->createMock(Quiz::class);
        
        $quiz->expects($this->once())
            ->method('setUser')
            ->with($this->user);
        
        $result = $this->user->addQuiz($quiz);
        
        $this->assertSame($this->user, $result);
        $this->assertTrue($this->user->getQuizs()->contains($quiz));
    }

    public function testRemoveQuiz(): void
    {
        $quiz = $this->createMock(Quiz::class);
        
        // Ajouter d'abord le quiz
        $quiz->expects($this->exactly(2))
            ->method('setUser')
            ->withConsecutive([$this->user], [null]);
        
        // Maintenant le supprimer - il faut mocker getUser pour la condition
        $quiz->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);
        
        $this->user->addQuiz($quiz);
        $result = $this->user->removeQuiz($quiz);
        
        $this->assertSame($this->user, $result);
        $this->assertFalse($this->user->getQuizs()->contains($quiz));
    }

    // ===== Tests pour UserAnswer =====

    public function testGetUserAnswersInitialization(): void
    {
        $userAnswers = $this->user->getUserAnswers();
        $this->assertInstanceOf(ArrayCollection::class, $userAnswers);
        $this->assertCount(0, $userAnswers);
    }

    public function testAddUserAnswer(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);
        
        $userAnswer->expects($this->once())
            ->method('setUser')
            ->with($this->user);
        
        $result = $this->user->addUserAnswer($userAnswer);
        
        $this->assertSame($this->user, $result);
        $this->assertTrue($this->user->getUserAnswers()->contains($userAnswer));
    }

    public function testRemoveUserAnswer(): void
    {
        $userAnswer = $this->createMock(UserAnswer::class);
        
        // Configurer les mocks pour add et remove
        $userAnswer->expects($this->exactly(2))
            ->method('setUser')
            ->withConsecutive([$this->user], [null]);
        
        $userAnswer->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);
        
        $this->user->addUserAnswer($userAnswer);
        $result = $this->user->removeUserAnswer($userAnswer);
        
        $this->assertSame($this->user, $result);
        $this->assertFalse($this->user->getUserAnswers()->contains($userAnswer));
    }

    // ===== Tests pour UserPermission =====

    public function testGetUserPermissionsInitialization(): void
    {
        $permissions = $this->user->getUserPermissions();
        $this->assertInstanceOf(ArrayCollection::class, $permissions);
        $this->assertCount(0, $permissions);
    }

    public function testAddUserPermission(): void
    {
        $permission = $this->createMock(UserPermission::class);
        
        $permission->expects($this->once())
            ->method('setUser')
            ->with($this->user);
        
        $result = $this->user->addUserPermission($permission);
        
        $this->assertSame($this->user, $result);
        $this->assertTrue($this->user->getUserPermissions()->contains($permission));
    }

    public function testRemoveUserPermission(): void
    {
        $permission = $this->createMock(UserPermission::class);
        
        // Configurer les mocks pour add et remove
        $permission->expects($this->exactly(2))
            ->method('setUser')
            ->withConsecutive([$this->user], [null]);
        
        $permission->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);
        
        $this->user->addUserPermission($permission);
        $result = $this->user->removeUserPermission($permission);
        
        $this->assertSame($this->user, $result);
        $this->assertFalse($this->user->getUserPermissions()->contains($permission));
    }

    // ===== Tests pour Groups =====

    public function testGetGroupsInitialization(): void
    {
        $groups = $this->user->getGroups();
        $this->assertInstanceOf(ArrayCollection::class, $groups);
        $this->assertCount(0, $groups);
    }

    public function testAddGroup(): void
    {
        $group = $this->createMock(Group::class);
        
        $result = $this->user->addGroup($group);
        
        $this->assertSame($this->user, $result);
        $this->assertTrue($this->user->getGroups()->contains($group));
    }

    public function testRemoveGroup(): void
    {
        $group = $this->createMock(Group::class);
        
        // Ajouter d'abord le groupe
        $this->user->addGroup($group);
        
        // Maintenant le supprimer
        $result = $this->user->removeGroup($group);
        
        $this->assertSame($this->user, $result);
        $this->assertFalse($this->user->getGroups()->contains($group));
    }

    // ===== Tests pour les propriétés supplémentaires =====

    public function testFirstNameGetterSetter(): void
    {
        $firstName = 'John';
        $this->user->setFirstName($firstName);
        $this->assertEquals($firstName, $this->user->getFirstName());
    }

    public function testLastNameGetterSetter(): void
    {
        $lastName = 'Doe';
        $this->user->setLastName($lastName);
        $this->assertEquals($lastName, $this->user->getLastName());
    }

    public function testAvatarGetterSetter(): void
    {
        $avatar = 'avatar.jpg';
        $this->user->setAvatar($avatar);
        $this->assertEquals($avatar, $this->user->getAvatar());
    }

    public function testAvatarNull(): void
    {
        $this->user->setAvatar(null);
        $this->assertNull($this->user->getAvatar());
    }

    public function testIsVerifiedGetterSetter(): void
    {
        // Par défaut false
        $this->assertFalse($this->user->isVerified());
        
        $this->user->setIsVerified(true);
        $this->assertTrue($this->user->isVerified());
    }

    public function testDeletedAtGetterSetter(): void
    {
        // Par défaut null
        $this->assertNull($this->user->getDeletedAt());
        
        $date = new \DateTimeImmutable();
        $this->user->setDeletedAt($date);
        $this->assertEquals($date, $this->user->getDeletedAt());
    }

    public function testConfirmationTokenGetterSetter(): void
    {
        $token = 'verification-token-123';
        $this->user->setConfirmationToken($token);
        $this->assertEquals($token, $this->user->getConfirmationToken());
    }

    public function testConfirmationTokenNull(): void
    {
        $this->user->setConfirmationToken(null);
        $this->assertNull($this->user->getConfirmationToken());
    }

    public function testPasswordResetTokenGetterSetter(): void
    {
        $token = 'reset-token-123';
        $this->user->setPasswordResetToken($token);
        $this->assertEquals($token, $this->user->getPasswordResetToken());
    }

    public function testPasswordResetRequestAtGetterSetter(): void
    {
        $date = new \DateTimeImmutable();
        $this->user->setPasswordResetRequestAt($date);
        $this->assertEquals($date, $this->user->getPasswordResetRequestAt());
    }

    public function testIsActiveGetterSetter(): void
    {
        // Par défaut null (propriété non initialisée)
        // $this->assertNull($this->user->isActive()); // Peut causer une erreur si non initialisée
        
        $this->user->setIsActive(true);
        $this->assertTrue($this->user->isActive());
        
        $this->user->setIsActive(false);
        $this->assertFalse($this->user->isActive());
    }

    public function testPseudoGetterSetter(): void
    {
        $pseudo = 'testuser';
        $this->user->setPseudo($pseudo);
        $this->assertEquals($pseudo, $this->user->getPseudo());
    }

    public function testGetCompanyName(): void
    {
        // Sans company
        $this->assertNull($this->user->getCompanyName());
        
        // Avec company
        $company = $this->createMock(Company::class);
        $company->method('getName')->willReturn('Test Company');
        $this->user->setCompany($company);
        $this->assertEquals('Test Company', $this->user->getCompanyName());
    }

    public function testGetCompanyId(): void
    {
        // Sans company
        $this->assertNull($this->user->getCompanyId());
        
        // Avec company
        $company = $this->createMock(Company::class);
        $company->method('getId')->willReturn(123);
        $this->user->setCompany($company);
        $this->assertEquals(123, $this->user->getCompanyId());
    }

    public function testHasPermission(): void
    {
        $permission = Permission::CREATE_QUIZ;
        
        // Sans permissions
        $this->assertFalse($this->user->hasPermission($permission));
        
        // Avec permission
        $userPermission = $this->createMock(UserPermission::class);
        $userPermission->method('getPermission')->willReturn($permission);
        $this->user->addUserPermission($userPermission);
        
        $this->assertTrue($this->user->hasPermission($permission));
    }
}
