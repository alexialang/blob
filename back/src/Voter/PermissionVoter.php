<?php

namespace App\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    private const SUPPORTED_PERMISSIONS = [
        'CREATE_QUIZ',
        'VIEW_RESULTS_ALL',
        'MANAGE_USERS',
        'ASSIGN_PERMISSIONS',
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::SUPPORTED_PERMISSIONS);
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

        return $user->getUserPermissions()->exists(
            fn($key, $perm) => $perm->getPermission() === $attribute
        );
    }
}
