<?php

namespace App\Entity;

use App\Repository\AnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnswerRepository::class)]
class Answer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $answer = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_correct = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $order_correct = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $pair_id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_intrus = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    private ?Question $question = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): static
    {
        $this->answer = $answer;

        return $this;
    }

    public function isCorrect(): ?bool
    {
        return $this->is_correct;
    }

    public function setIsCorrect(?bool $is_correct): static
    {
        $this->is_correct = $is_correct;

        return $this;
    }

    public function getOrderCorrect(): ?string
    {
        return $this->order_correct;
    }

    public function setOrderCorrect(?string $order_correct): static
    {
        $this->order_correct = $order_correct;

        return $this;
    }

    public function getPairId(): ?string
    {
        return $this->pair_id;
    }

    public function setPairId(?string $pair_id): static
    {
        $this->pair_id = $pair_id;

        return $this;
    }

    public function isIntrus(): ?bool
    {
        return $this->is_intrus;
    }

    public function setIsIntrus(?bool $is_intrus): static
    {
        $this->is_intrus = $is_intrus;

        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

        return $this;
    }
}
