<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Votre compte n\'est pas encore vérifié. Vérifiez vos emails.');
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Votre compte a été désactivé.');
        }

        if (null !== $user->getDeletedAt()) {
            throw new CustomUserMessageAccountStatusException('Ce compte n\'existe plus.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
