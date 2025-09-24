<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    private UserChecker $userChecker;

    protected function setUp(): void
    {
        $this->userChecker = new UserChecker();
    }

    public function testCheckPreAuthWithNonUserInterface(): void
    {
        $user = $this->createMock(UserInterface::class);

        // Should not throw any exception
        $this->userChecker->checkPreAuth($user);
        $this->assertTrue(true); // Just to ensure the test passes
    }

    public function testCheckPreAuthWithVerifiedActiveUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isVerified')->willReturn(true);
        $user->method('isActive')->willReturn(true);
        $user->method('getDeletedAt')->willReturn(null);

        // Should not throw any exception
        $this->userChecker->checkPreAuth($user);
        $this->assertTrue(true); // Just to ensure the test passes
    }

    public function testCheckPreAuthWithUnverifiedUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isVerified')->willReturn(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Votre compte n\'est pas encore vérifié. Vérifiez vos emails.');

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthWithInactiveUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isVerified')->willReturn(true);
        $user->method('isActive')->willReturn(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Votre compte a été désactivé.');

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthWithDeletedUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isVerified')->willReturn(true);
        $user->method('isActive')->willReturn(true);
        $user->method('getDeletedAt')->willReturn(new \DateTimeImmutable());

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Ce compte n\'existe plus.');

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPostAuth(): void
    {
        $user = $this->createMock(UserInterface::class);

        // Method should do nothing and not throw exception
        $this->userChecker->checkPostAuth($user);
        $this->assertTrue(true); // Just to ensure the test passes
    }
}
