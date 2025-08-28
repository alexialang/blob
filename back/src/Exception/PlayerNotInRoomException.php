<?php

namespace App\Exception;

class PlayerNotInRoomException extends \Exception
{
    public function __construct(string $roomCode)
    {
        parent::__construct("Vous n'êtes pas dans le salon '$roomCode'", 403);
    }
}
