<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Entity\Company;
use App\Entity\Quiz;
use App\Entity\Group;
use App\Entity\UserPermission;
use App\Enum\Permission;
use App\Voter\PermissionVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionVoterTest extends TestCase
{
    private PermissionVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new PermissionVoter();
    }

    public function testSupportsValidAttributes(): void
    {
        $this->assertTrue($this->voter->supports('create_quiz', null));
        $this->assertTrue($this->voter->supports('manage_users', null));
        $this->assertTrue($this->voter->supports('view_results', null));
    }

    public function testDoesNotSupportInvalidAttributes(): void
    {
        $this->assertFalse($this->voter->supports('invalid_permission', null));
        $this->assertFalse($this->voter->supports('random_string', null));
    }

    public function testAdminUserHasAllPermissions(): void
    {
        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($adminUser);

        $result = $this->voter->vote($token, null, ['CREATE_QUIZ']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);

        $result = $this->voter->vote($token, null, ['MANAGE_USERS']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);

        $result = $this->voter->vote($token, null, ['VIEW_RESULTS']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRegularUserWithPermission(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        // Ajouter la permission CREATE_QUIZ
        $permission = new UserPermission();
        $permission->setUser($user);
        $permission->setPermission(Permission::CREATE_QUIZ);
        $user->addUserPermission($permission);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, null, ['CREATE_QUIZ']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRegularUserWithoutPermission(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        // Pas de permission CREATE_QUIZ

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, null, ['CREATE_QUIZ']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAnonymousUserDenied(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, null, ['CREATE_QUIZ']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUserCanAccessOwnCompany(): void
    {
        $company = new Company();
        $company->setName('Test Company');

        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $user->setCompany($company);

        // Ajouter la permission VIEW_RESULTS
        $permission = new UserPermission();
        $permission->setUser($user);
        $permission->setPermission(Permission::VIEW_RESULTS);
        $user->addUserPermission($permission);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $company, ['VIEW_RESULTS']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotAccessOtherCompany(): void
    {
        $userCompany = new Company();
        $userCompany->setName('User Company');

        $otherCompany = new Company();
        $otherCompany->setName('Other Company');

        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $user->setCompany($userCompany);

        // Ajouter la permission VIEW_RESULTS
        $permission = new UserPermission();
        $permission->setUser($user);
        $permission->setPermission(Permission::VIEW_RESULTS);
        $user->addUserPermission($permission);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $otherCompany, ['VIEW_RESULTS']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUserCanAccessOwnQuiz(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setUser($user);

        // Ajouter la permission CREATE_QUIZ
        $permission = new UserPermission();
        $permission->setUser($user);
        $permission->setPermission(Permission::CREATE_QUIZ);
        $user->addUserPermission($permission);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $quiz, ['CREATE_QUIZ']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCanAccessQuizFromSameCompany(): void
    {
        $company = new Company();
        $company->setName('Test Company');

        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $user->setCompany($company);

        $quizOwner = new User();
        $quizOwner->setCompany($company);

        $quiz = new Quiz();
        $quiz->setTitle('Company Quiz');
        $quiz->setUser($quizOwner);

        // Ajouter la permission CREATE_QUIZ
        $permission = new UserPermission();
        $permission->setUser($user);
        $permission->setPermission(Permission::CREATE_QUIZ);
        $user->addUserPermission($permission);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $quiz, ['CREATE_QUIZ']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotAccessQuizFromOtherCompany(): void
    {
        $userCompany = new Company();
        $userCompany->setName('User Company');

        $otherCompany = new Company();
        $otherCompany->setName('Other Company');

        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $user->setCompany($userCompany);

        $quizOwner = new User();
        $quizOwner->setCompany($otherCompany);

        $quiz = new Quiz();
        $quiz->setTitle('Other Company Quiz');
        $quiz->setUser($quizOwner);

        // Ajouter la permission CREATE_QUIZ
        $permission = new UserPermission();
        $permission->setUser($user);
        $permission->setPermission(Permission::CREATE_QUIZ);
        $user->addUserPermission($permission);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $quiz, ['CREATE_QUIZ']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUserCanAccessGroupFromSameCompany(): void
    {
        $company = new Company();
        $company->setName('Test Company');

        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $user->setCompany($company);

        $group = new Group();
        $group->setName('Test Group');
        $group->setCompany($company);

        // Ajouter la permission MANAGE_USERS
        $permission = new UserPermission();
        $permission->setUser($user);
        $permission->setPermission(Permission::MANAGE_USERS);
        $user->addUserPermission($permission);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $group, ['MANAGE_USERS']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserWithoutCompanyCannotAccessCompanyResources(): void
    {
        $company = new Company();
        $company->setName('Test Company');

        $user = new User();
        $user->setRoles(['ROLE_USER']);
        // Pas d'entreprise assignée

        // Ajouter la permission VIEW_RESULTS
        $permission = new UserPermission();
        $permission->setUser($user);
        $permission->setPermission(Permission::VIEW_RESULTS);
        $user->addUserPermission($permission);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $company, ['VIEW_RESULTS']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testInvalidPermissionEnum(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, null, ['INVALID_PERMISSION']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}
