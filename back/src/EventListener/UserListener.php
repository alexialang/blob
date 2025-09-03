<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\UserService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;


#[AsEntityListener(event: Events::postPersist, method: 'onPostPersist', entity: User::class)]
class UserListener
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function onPostPersist(User $user): void
    {
        if (null !== $user->getConfirmationToken() && !$user->isVerified()) {
            $this->userService->sendEmail(
                $user->getEmail(),
                $user->getFirstName(),
                $user->getConfirmationToken()
            );
        }
    }
}
