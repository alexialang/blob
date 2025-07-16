<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['question:read', 'quiz:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['question:read', 'question:create', 'quiz:read'])]
    private ?string $question = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[Groups(['question:read', 'question:create'])]
    private ?Quiz $quiz = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[Groups(['question:read', 'question:create', 'quiz:read'])]
    private ?TypeQuestion $type_question = null;

    /**
     * @var Collection<int, Answer>
     */
    #[ORM\OneToMany(targetEntity: Answer::class, mappedBy: 'question')]
    #[Groups(['question:read', 'quiz:read'])]
    private Collection $answers;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;
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

    public function getTypeQuestion(): ?TypeQuestion
    {
        return $this->type_question;
    }

    public function setTypeQuestion(?TypeQuestion $type_question): static
    {
        $this->type_question = $type_question;
        return $this;
    }

    /**
     * @return Collection<int, Answer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): static
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
            $answer->setQuestion($this);
        }
        return $this;
    }

    public function removeAnswer(Answer $answer): static
    {
        if ($this->answers->removeElement($answer)) {
            if ($answer->getQuestion() === $this) {
                $answer->setQuestion(null);
            }
        }
        return $this;
    }
}
