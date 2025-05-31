<?php

namespace App\Message\Handler;

use App\Message\Mailer\RegistrationConfirmationEmailMessage;
use App\Service\UserService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
readonly class RegistrationConfirmationEmailHandler
{
    public function __construct(
        private UserService     $userService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(RegistrationConfirmationEmailMessage $message): void
    {
        $this->logger->info('Handler déclenché pour envoyer l\'email à ' . $message->email);

        $this->userService->sendEmail(
            $message->email,
            $message->firstName,
            $message->confirmationToken
        );

        $this->logger->info('Email envoyé via UserService pour ' . $message->email);
    }
}