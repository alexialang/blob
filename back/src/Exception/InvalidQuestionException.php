<?php

namespace App\Exception;

class InvalidQuestionException extends \Exception
{
    public function __construct(int $questionId, string $reason = "Question invalide")
    {
        parent::__construct("$reason pour la question ID $questionId", 400);
    }
}
