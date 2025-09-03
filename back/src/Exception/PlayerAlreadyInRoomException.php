<?php

namespace App\Exception;

class PlayerAlreadyInRoomException extends \Exception
{
    public function __construct(string $roomCode)
    {
        parent::__construct("Vous êtes déjà dans le salon '$roomCode'", 409);
    }
}
