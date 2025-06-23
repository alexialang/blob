<?php

namespace App\Message\Mailer;
class RegistrationConfirmationEmailMessage
{
    public string $email;
    public string $firstName;
    public string $confirmationToken;

    public function __construct(string $email, string $firstName, string $confirmationToken)
    {
        $this->email             = $email;
        $this->firstName         = $firstName;
        $this->confirmationToken = $confirmationToken;
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
