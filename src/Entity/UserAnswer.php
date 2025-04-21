<?php

namespace App\Entity;

use App\Repository\UserAnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAnswerRepository::class)]
class UserAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_attempt = null;

    #[ORM\Column]
    private ?int $total_score = null;

    #[ORM\ManyToOne(inversedBy: 'userAnswers')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userAnswers')]
    private ?Quiz $quiz = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateAttempt(): ?\DateTimeInterface
    {
        return $this->date_attempt;
    }

    public function setDateAttempt(\DateTimeInterface $date_attempt): static
    {
        $this->date_attempt = $date_attempt;

        return $this;
    }

    public function getTotalScore(): ?int
    {
        return $this->total_score;
    }

    public function setTotalScore(int $total_score): static
    {
        $this->total_score = $total_score;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;

        return $this;
    }
}
