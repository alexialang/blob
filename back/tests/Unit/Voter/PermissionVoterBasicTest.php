<?php

namespace App\Tests\Unit\Voter;

use App\Entity\User;
use App\Enum\Permission;
use App\Voter\PermissionVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PermissionVoterBasicTest extends TestCase
{
    private PermissionVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new PermissionVoter();
    }

    public function testVoteOnAttributeWithNonUserToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, null, ['CREATE_QUIZ']);

        $this->assertEquals(-1, $result); // VoterInterface::ACCESS_DENIED
    }

    public function testVoteOnAttributeWithAdminUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isAdmin')->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, null, ['CREATE_QUIZ']);

        $this->assertEquals(1, $result); // VoterInterface::ACCESS_GRANTED
    }

    public function testVoteOnAttributeWithInvalidPermission(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isAdmin')->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, null, ['INVALID_PERMISSION']);

        $this->assertEquals(0, $result); // VoterInterface::ACCESS_ABSTAIN
    }

    public function testVoteOnAttributeWithValidPermissionButUserDoesNotHaveIt(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isAdmin')->willReturn(false);
        $user->method('hasPermission')->with(Permission::CREATE_QUIZ)->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, null, ['CREATE_QUIZ']);

        $this->assertEquals(-1, $result); // VoterInterface::ACCESS_DENIED
    }

    public function testVoteOnAttributeWithValidPermissionAndUserHasIt(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isAdmin')->willReturn(false);
        $user->method('hasPermission')->with(Permission::CREATE_QUIZ)->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, null, ['CREATE_QUIZ']);

        $this->assertEquals(1, $result); // VoterInterface::ACCESS_GRANTED
    }

    public function testVoteOnAttributeWithException(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isAdmin')->willThrowException(new \Exception('Test exception'));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, null, ['CREATE_QUIZ']);

        $this->assertEquals(-1, $result); // VoterInterface::ACCESS_DENIED
    }
}
