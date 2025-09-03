<?php

namespace App\Exception;

class InsufficientPlayersException extends \Exception
{
    public function __construct(int $minRequired = 2)
    {
        parent::__construct("Il faut au moins $minRequired joueurs pour commencer", 400);
    }
}
