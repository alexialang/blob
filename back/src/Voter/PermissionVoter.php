<?php

namespace App\Voter;

use App\Entity\User;
use App\Enum\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            Permission::CREATE_QUIZ->value,
            Permission::MANAGE_USERS->value,
            Permission::VIEW_RESULTS->value,
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }


        if ($user->isAdmin()) {
            return true;
        }


        return $user->hasPermission(Permission::from($attribute));
    }
}
