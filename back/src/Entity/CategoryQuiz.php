<?php

namespace App\Entity;

use App\Repository\CategoryQuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategoryQuizRepository::class)]
class CategoryQuiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['quiz:read', 'quiz:organized'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['quiz:read', 'quiz:organized'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Quiz>
     */
    #[ORM\OneToMany(targetEntity: Quiz::class, mappedBy: 'category')]
    private Collection $quizs;

    public function __construct()
    {
        $this->quizs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizs(): Collection
    {
        return $this->quizs;
    }

    public function addQuiz(Quiz $quiz): static
    {
        if (!$this->quizs->contains($quiz)) {
            $this->quizs->add($quiz);
            $quiz->setCategory($this);
        }

        return $this;
    }

    public function removeQuiz(Quiz $quiz): static
    {
        if ($this->quizs->removeElement($quiz)) {
            if ($quiz->getCategory() === $this) {
                $quiz->setCategory(null);
            }
        }

        return $this;
    }
}
