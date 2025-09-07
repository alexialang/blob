<?php

namespace App\Tests\Unit\Message\Handler;

use App\Message\Handler\RegistrationConfirmationEmailHandler;
use App\Message\Mailer\RegistrationConfirmationEmailMessage;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class RegistrationConfirmationEmailHandlerTest extends TestCase
{
    public function testHandlerInvoke(): void
    {
        $userService = $this->createMock(UserService::class);
        $message = new RegistrationConfirmationEmailMessage('test@example.com', 'Jane', 'confirm123');
        
        $userService
            ->expects($this->once())
            ->method('sendEmail')
            ->with('test@example.com', 'Jane', 'confirm123');
        
        $handler = new RegistrationConfirmationEmailHandler($userService);
        $handler($message);
    }
}

