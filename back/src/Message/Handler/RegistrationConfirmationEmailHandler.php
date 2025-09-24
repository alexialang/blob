<?php

namespace App\Message\Handler;

use App\Message\Mailer\RegistrationConfirmationEmailMessage;
use App\Service\UserService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class RegistrationConfirmationEmailHandler
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    public function __invoke(RegistrationConfirmationEmailMessage $message): void
    {
        $this->userService->sendEmail(
            $message->email,
            $message->firstName,
            $message->confirmationToken
        );
    }
}
