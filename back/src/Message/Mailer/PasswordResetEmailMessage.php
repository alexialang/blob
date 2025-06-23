<?php

namespace App\Message\Mailer;

class PasswordResetEmailMessage
{
    public string $email;
    public string $firstName;
    public string $resetToken;

    public function __construct(string $email, string $firstName, string $resetToken)
    {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->resetToken = $resetToken;
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
