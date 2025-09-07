<?php

namespace App\Exception;

class UnauthorizedGameActionException extends \Exception
{
    public function __construct(string $action = 'action')
    {
        parent::__construct("Seul le créateur peut effectuer cette $action", 403);
    }
}
