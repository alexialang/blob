<?php

namespace App\Voter;

use App\Entity\User;
use App\Entity\Company;
use App\Entity\Quiz;
use App\Entity\Group;
use App\Enum\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        $allowed = [
            strtolower(Permission::CREATE_QUIZ->value),
            strtolower(Permission::MANAGE_USERS->value),
            strtolower(Permission::VIEW_RESULTS->value),
        ];

        return in_array(strtolower($attribute), $allowed, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        try {
            $authUser = $token->getUser();
            
            if (!$authUser instanceof User) {
                return false;
            }

                        if ($authUser->isAdmin()) {
                return true;
            }

            try {
                $permissionEnum = Permission::from($attribute);
            } catch (\ValueError) {
                return false;
            }

            if (!$authUser->hasPermission($permissionEnum)) {
                return false;
            }
            if ($subject === null) {
                return true;
            }
            return $this->canAccessSubject($authUser, $subject);

        } catch (\Exception $e) {
            return false;
        }
    }
    private function canAccessSubject(User $authUser, mixed $subject): bool
    {
        if ($subject instanceof User) {
            return $this->sameCompany($authUser, $subject->getCompany());
        }
        
        if ($subject instanceof Company) {
            return $this->sameCompany($authUser, $subject);
        }
        
        if ($subject instanceof Quiz) {
            return $this->canAccessQuiz($authUser, $subject);
        }
        
        if ($subject instanceof Group) {
            return $this->canAccessGroup($authUser, $subject);
        }

        return true;
    }

    private function canAccessQuiz(User $authUser, Quiz $quiz): bool
    {

        if ($quiz->getCompany()) {
            $result = $this->sameCompany($authUser, $quiz->getCompany());
            return $result;
        }
        
        if ($quiz->getUser() && $quiz->getUser()->getId() === $authUser->getId()) {
            return true;
        }
        
        if ($quiz->getUser() && $quiz->getUser()->getCompany() && $authUser->getCompany()) {
            $result = $this->sameCompany($authUser, $quiz->getUser()->getCompany());
            return $result;
        }
        
        return false;
    }


    private function canAccessGroup(User $authUser, Group $group): bool
    {
        return $this->sameCompany($authUser, $group->getCompany());
    }


    private function sameCompany(User $authUser, ?Company $targetCompany): bool
    {
        $userCompany = $authUser->getCompany();
        
        if (!$userCompany) {
            return false;
        }
        
        if (!$targetCompany) {
            return false;
        }
        
        return $userCompany->getId() === $targetCompany->getId();
    }
}
