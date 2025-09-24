<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\User;
use App\EventListener\UserListener;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class UserListenerTest extends TestCase
{
    private UserListener $listener;
    private UserService $userService;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserService::class);
        $this->listener = new UserListener($this->userService);
    }

    public function testOnPostPersistWithUnverifiedUserWithToken(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getConfirmationToken')->willReturn('token-123');
        $user->method('isVerified')->willReturn(false);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getFirstName')->willReturn('Test');

        $this->userService->expects($this->once())
            ->method('sendEmail')
            ->with('test@example.com', 'Test', 'token-123');

        $this->listener->onPostPersist($user);
    }

    public function testOnPostPersistWithVerifiedUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getConfirmationToken')->willReturn('token-123');
        $user->method('isVerified')->willReturn(true);

        $this->userService->expects($this->never())
            ->method('sendEmail');

        $this->listener->onPostPersist($user);
    }

    public function testOnPostPersistWithoutToken(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getConfirmationToken')->willReturn(null);
        $user->method('isVerified')->willReturn(false);

        $this->userService->expects($this->never())
            ->method('sendEmail');

        $this->listener->onPostPersist($user);
    }
}
