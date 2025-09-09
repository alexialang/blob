<?php

namespace App\Tests\Unit\Message\Handler;

use App\Message\Handler\PasswordResetEmailHandler;
use App\Message\Mailer\PasswordResetEmailMessage;
use App\Service\UserPasswordResetService;
use PHPUnit\Framework\TestCase;

class PasswordResetEmailHandlerTest extends TestCase
{
    public function testHandlerInvoke(): void
    {
        $userPasswordResetService = $this->createMock(UserPasswordResetService::class);
        $message = new PasswordResetEmailMessage('test@example.com', 'John', 'reset123');

        $userPasswordResetService
            ->expects($this->once())
            ->method('sendPasswordResetEmail')
            ->with('test@example.com', 'John', 'reset123');

        $handler = new PasswordResetEmailHandler($userPasswordResetService);
        $handler($message);
    }
}
