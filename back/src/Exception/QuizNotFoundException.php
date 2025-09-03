<?php

namespace App\Exception;

class QuizNotFoundException extends \Exception
{
    public function __construct(int $quizId)
    {
        parent::__construct("Quiz avec ID $quizId non trouvé", 404);
    }
}
