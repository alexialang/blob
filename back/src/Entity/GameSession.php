<?php

namespace App\Entity;

use App\Repository\GameSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameSessionRepository::class)]
class GameSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $gameCode = null;

    #[ORM\OneToOne(targetEntity: Room::class, inversedBy: 'gameSession')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Room $room = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'playing';

    #[ORM\Column]
    private ?int $currentQuestionIndex = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $sharedScores = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $currentQuestionStartedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $currentQuestionDuration = 30;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
        $this->gameCode = uniqid('game_');
        $this->sharedScores = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGameCode(): ?string
    {
        return $this->gameCode;
    }

    public function setGameCode(string $gameCode): static
    {
        $this->gameCode = $gameCode;
        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCurrentQuestionIndex(): ?int
    {
        return $this->currentQuestionIndex;
    }

    public function setCurrentQuestionIndex(int $currentQuestionIndex): static
    {
        $this->currentQuestionIndex = $currentQuestionIndex;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    public function getSharedScores(): ?array
    {
        return $this->sharedScores;
    }

    public function setSharedScores(?array $sharedScores): static
    {
        $this->sharedScores = $sharedScores;
        return $this;
    }

    public function getCurrentQuestionStartedAt(): ?\DateTimeImmutable
    {
        return $this->currentQuestionStartedAt;
    }

    public function setCurrentQuestionStartedAt(\DateTimeImmutable $currentQuestionStartedAt): static
    {
        $this->currentQuestionStartedAt = $currentQuestionStartedAt;
        return $this;
    }

    public function getCurrentQuestionDuration(): ?int
    {
        return $this->currentQuestionDuration;
    }

    public function setCurrentQuestionDuration(int $currentQuestionDuration): static
    {
        $this->currentQuestionDuration = $currentQuestionDuration;
        return $this;
    }
}
