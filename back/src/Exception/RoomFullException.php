<?php

namespace App\Exception;

class RoomFullException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Le salon est complet', 400);
    }
}
