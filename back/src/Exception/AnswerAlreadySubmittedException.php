<?php

namespace App\Exception;

class AnswerAlreadySubmittedException extends \Exception
{
    public function __construct(int $questionId)
    {
        parent::__construct("Réponse déjà soumise pour la question ID $questionId", 409);
    }
}
