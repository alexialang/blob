<?php

namespace App\Message\Handler;

use App\Message\Mailer\PasswordResetEmailMessage;
use App\Service\UserPasswordResetService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class PasswordResetEmailHandler
{
    public function __construct(
        private UserPasswordResetService $userPasswordResetService
    ) {}

    public function __invoke(PasswordResetEmailMessage $message): void
    {
        $this->userPasswordResetService->sendPasswordResetEmail(
            $message->email,
            $message->firstName,
            $message->resetToken
        );
    }
}
