<?php

namespace App\Tests\Unit\Message\Mailer;

use App\Message\Mailer\RegistrationConfirmationEmailMessage;
use PHPUnit\Framework\TestCase;

class RegistrationConfirmationEmailMessageTest extends TestCase
{
    public function testMessageCreation(): void
    {
        $email = 'test@example.com';
        $firstName = 'Jane';
        $confirmationToken = 'xyz789';

        $message = new RegistrationConfirmationEmailMessage($email, $firstName, $confirmationToken);

        $this->assertEquals($email, $message->email);
        $this->assertEquals($firstName, $message->firstName);
        $this->assertEquals($confirmationToken, $message->confirmationToken);

        $this->assertEquals($email, $message->getEmail());
        $this->assertEquals($firstName, $message->getFirstName());
        $this->assertEquals($confirmationToken, $message->getConfirmationToken());
    }
}
