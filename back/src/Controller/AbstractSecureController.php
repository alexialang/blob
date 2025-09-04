<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractSecureController extends AbstractController
{
    /**
     * Helper method to get the current user with proper type casting
     */
    protected function getCurrentUser(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Get the current user or throw an exception if not authenticated
     */
    protected function getRequiredUser(): User
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié');
        }
        return $user;
    }
}
