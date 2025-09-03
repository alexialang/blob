<?php

namespace App\Exception;

class GameNotStartedException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Le jeu n'est pas en cours", 400);
    }
}
