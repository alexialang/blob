<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $roomCode = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\Column]
    private ?int $maxPlayers = 4;

    #[ORM\Column]
    private ?bool $isTeamMode = false;

    #[ORM\Column(length: 50)]
    private ?string $status = 'waiting'; // waiting, playing, finished

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $gameStartedAt = null;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: RoomPlayer::class, cascade: ['persist', 'remove'])]
    private Collection $players;

    #[ORM\OneToOne(mappedBy: 'room', targetEntity: GameSession::class, cascade: ['persist', 'remove'])]
    private ?GameSession $gameSession = null;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->roomCode = uniqid('room_');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoomCode(): ?string
    {
        return $this->roomCode;
    }

    public function setRoomCode(string $roomCode): static
    {
        $this->roomCode = $roomCode;
        return $this;
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

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;
        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;
        return $this;
    }

    public function getMaxPlayers(): ?int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): static
    {
        $this->maxPlayers = $maxPlayers;
        return $this;
    }

    public function isTeamMode(): ?bool
    {
        return $this->isTeamMode;
    }

    public function setIsTeamMode(bool $isTeamMode): static
    {
        $this->isTeamMode = $isTeamMode;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getGameStartedAt(): ?\DateTimeImmutable
    {
        return $this->gameStartedAt;
    }

    public function setGameStartedAt(?\DateTimeImmutable $gameStartedAt): static
    {
        $this->gameStartedAt = $gameStartedAt;
        return $this;
    }

    /**
     * @return Collection<int, RoomPlayer>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(RoomPlayer $player): static
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
            $player->setRoom($this);
        }

        return $this;
    }

    public function removePlayer(RoomPlayer $player): static
    {
        if ($this->players->removeElement($player)) {
            if ($player->getRoom() === $this) {
                $player->setRoom(null);
            }
        }

        return $this;
    }

    public function getGameSession(): ?GameSession
    {
        return $this->gameSession;
    }

    public function setGameSession(?GameSession $gameSession): static
    {
        if ($gameSession === null && $this->gameSession !== null) {
            $this->gameSession->setRoom(null);
        }

        if ($gameSession !== null && $gameSession->getRoom() !== $this) {
            $gameSession->setRoom($this);
        }

        $this->gameSession = $gameSession;

        return $this;
    }

    public function getCurrentPlayerCount(): int
    {
        return $this->players->count();
    }

    public function isAvailable(): bool
    {
        return $this->status === 'waiting' && $this->getCurrentPlayerCount() < $this->maxPlayers;
    }
}
