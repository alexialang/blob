<?php

namespace App\Tests\Unit\Message\Mailer;

use App\Message\Mailer\PasswordResetEmailMessage;
use PHPUnit\Framework\TestCase;

class PasswordResetEmailMessageTest extends TestCase
{
    public function testMessageCreation(): void
    {
        $email = 'test@example.com';
        $firstName = 'John';
        $resetToken = 'abc123';
        
        $message = new PasswordResetEmailMessage($email, $firstName, $resetToken);
        
        $this->assertEquals($email, $message->email);
        $this->assertEquals($firstName, $message->firstName);
        $this->assertEquals($resetToken, $message->resetToken);
        
        $this->assertEquals($email, $message->getEmail());
        $this->assertEquals($firstName, $message->getFirstName());
        $this->assertEquals($resetToken, $message->getResetToken());
    }
}

