<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use ReflectionClass;

class UserCheckerTest extends TestCase
{
    private UserChecker $userChecker;
    private User $user;

    protected function setUp(): void
    {
        $this->userChecker = new UserChecker();
        $this->user = new User();
    }

    private function setEntityId($entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }

    public function testCheckPreAuthWithVerifiedAndActiveUser(): void
    {
        $this->setEntityId($this->user, 1);
        $this->user->setIsVerified(true);
        $this->user->setIsActive(true);

        $this->userChecker->checkPreAuth($this->user);
        $this->assertTrue(true); // Test réussi si aucune exception n'est levée
    }

    public function testCheckPreAuthWithUnverifiedUser(): void
    {
        $this->setEntityId($this->user, 1);
        $this->user->setIsVerified(false);
        $this->user->setIsActive(true);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Votre compte n\'est pas encore vérifié. Vérifiez vos emails.');
        $this->userChecker->checkPreAuth($this->user);
    }

    public function testCheckPreAuthWithInactiveUser(): void
    {
        $this->setEntityId($this->user, 1);
        $this->user->setIsVerified(true);
        $this->user->setIsActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Votre compte a été désactivé.');
        $this->userChecker->checkPreAuth($this->user);
    }

    public function testCheckPreAuthWithDeletedUser(): void
    {
        $this->setEntityId($this->user, 1);
        $this->user->setIsVerified(true);
        $this->user->setIsActive(true);
        $this->user->setDeletedAt(new \DateTimeImmutable());

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Ce compte n\'existe plus.');
        $this->userChecker->checkPreAuth($this->user);
    }

    public function testCheckPreAuthWithNonUserInterface(): void
    {
        $nonUser = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);

        $this->userChecker->checkPreAuth($nonUser);
        $this->assertTrue(true); // Test réussi si aucune exception n'est levée
    }

    public function testCheckPostAuth(): void
    {
        $this->setEntityId($this->user, 1);
        $this->user->setIsVerified(true);
        $this->user->setIsActive(true);

        $this->userChecker->checkPostAuth($this->user);
        $this->assertTrue(true); // Test réussi si aucune exception n'est levée
    }
}
