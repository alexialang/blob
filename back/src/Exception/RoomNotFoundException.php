<?php

namespace App\Exception;

class RoomNotFoundException extends \Exception
{
    public function __construct(string $roomCode)
    {
        parent::__construct("Salon '$roomCode' non trouvé", 404);
    }
}
