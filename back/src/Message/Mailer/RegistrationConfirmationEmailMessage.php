<?php

namespace App\Message\Mailer;

class RegistrationConfirmationEmailMessage
{
    public function __construct(public string $email, public string $firstName, public string $confirmationToken)
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

    public function getConfirmationToken(): string
    {
        return $this->confirmationToken;
    }
}
