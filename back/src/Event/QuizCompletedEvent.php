<?php

namespace App\Event;

use App\Entity\User;
use App\Entity\UserAnswer;
use Symfony\Contracts\EventDispatcher\Event;

class QuizCompletedEvent extends Event
{
    public const NAME = 'quiz.completed';

    public function __construct(
        private readonly UserAnswer $userAnswer,
        private readonly User $user,
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
