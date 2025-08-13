<?php

namespace App\Event;

use App\Entity\UserAnswer;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class QuizCompletedEvent extends Event
{
    public const NAME = 'quiz.completed';

    public function __construct(
        private UserAnswer $userAnswer,
        private User $user
    ) {
    }

    public function getUserAnswer(): UserAnswer
    {
        return $this->userAnswer;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getScore(): ?int
    {
        return $this->userAnswer->getTotalScore();
    }
}
