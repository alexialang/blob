<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Enum\Status;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['quiz:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['quiz:read', 'quiz:create'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['quiz:read', 'quiz:create'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['quiz:read', 'quiz:create'])]
    private ?bool $is_public = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['quiz:read'])]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(length: 50, enumType: Status::class)]
    #[Groups(['quiz:read', 'quiz:create'])]
    private ?Status $status = null;

    #[ORM\ManyToOne(inversedBy: 'quizs')]
    #[Groups(['quiz:read'])]
    private ?Company $company = null;

    #[ORM\ManyToOne(inversedBy: 'quizs')]
    #[Groups(['quiz:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'quizs')]
    #[Groups(['quiz:read', 'quiz:create'])]
    private ?CategoryQuiz $category = null;

    /**
     * @var Collection<int, Question>
     */
    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'quiz')]
    #[Groups(['quiz:read'])]
    private Collection $questions;

    /**
     * @var Collection<int, UserAnswer>
     */
    #[ORM\OneToMany(targetEntity: UserAnswer::class, mappedBy: 'quiz')]
    #[Groups(['quiz:read'])]
    private Collection $userAnswers;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->userAnswers = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->is_public;
    }

    public function setIsPublic(bool $is_public): static
    {
        $this->is_public = $is_public;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeInterface $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
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

    public function getCategory(): ?CategoryQuiz
    {
        return $this->category;
    }

    public function setCategory(?CategoryQuiz $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setQuiz($this);
        }
        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getQuiz() === $this) {
                $question->setQuiz(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, UserAnswer>
     */
    public function getUserAnswers(): Collection
    {
        return $this->userAnswers;
    }

    public function addUserAnswer(UserAnswer $userAnswer): static
    {
        if (!$this->userAnswers->contains($userAnswer)) {
            $this->userAnswers->add($userAnswer);
            $userAnswer->setQuiz($this);
        }
        return $this;
    }

    public function removeUserAnswer(UserAnswer $userAnswer): static
    {
        if ($this->userAnswers->removeElement($userAnswer)) {
            if ($userAnswer->getQuiz() === $this) {
                $userAnswer->setQuiz(null);
            }
        }
        return $this;
    }
}
