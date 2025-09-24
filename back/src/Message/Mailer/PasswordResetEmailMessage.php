<?php

namespace App\Message\Mailer;

class PasswordResetEmailMessage
{
    public function __construct(public string $email, public string $firstName, public string $resetToken)
    {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getResetToken(): string
    {
        return $this->resetToken;
    }
}
