<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        
        if ($user instanceof User) {
            $payload = $event->getData();
            $payload['userId'] = $user->getId();
            $payload['pseudo'] = $user->getPseudo();
            $payload['firstName'] = $user->getFirstName();
            $payload['lastName'] = $user->getLastName();
            
            $event->setData($payload);
        }
    }
}
