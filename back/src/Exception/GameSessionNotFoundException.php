<?php

namespace App\Exception;

class GameSessionNotFoundException extends \Exception
{
    public function __construct(string $gameCode)
    {
        parent::__construct("Jeu avec le code '$gameCode' non trouvé", 404);
    }
}
