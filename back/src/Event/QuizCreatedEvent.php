<?php

namespace App\Event;

use App\Entity\Quiz;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class QuizCreatedEvent extends Event
{
    public const NAME = 'quiz.created';

    public function __construct(
        private Quiz $quiz,
        private User $user,
    ) {
    }

    public function getQuiz(): Quiz
    {
        return $this->quiz;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
