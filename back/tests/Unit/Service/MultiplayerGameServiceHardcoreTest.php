<?php

namespace App\Tests\Unit\Service;

use App\Entity\GameSession;
use App\Entity\Quiz;
use App\Entity\Room;
use App\Entity\RoomPlayer;
use App\Entity\User;
use App\Exception\InvalidQuestionException;
use App\Exception\QuizNotFoundException;
use App\Exception\RoomNotFoundException;
use App\Repository\GameSessionRepository;
use App\Repository\QuizRepository;
use App\Repository\RoomPlayerRepository;
use App\Repository\RoomRepository;
use App\Service\MultiplayerGameService;
use App\Service\MultiplayerScoreService;
use App\Service\MultiplayerTimingService;
use App\Service\MultiplayerValidationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;

class MultiplayerGameServiceHardcoreTest extends TestCase
{
    private MultiplayerGameService $service;
    private EntityManagerInterface $entityManager;
    private HubInterface $mercureHub;
    private RoomRepository $roomRepository;
    private GameSessionRepository $gameSessionRepository;
    private MultiplayerTimingService $timingService;
    private MultiplayerScoreService $scoreService;
    private MultiplayerValidationService $validationService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->mercureHub = $this->createMock(HubInterface::class);
        $this->roomRepository = $this->createMock(RoomRepository::class);
        $this->gameSessionRepository = $this->createMock(GameSessionRepository::class);
        $this->timingService = $this->createMock(MultiplayerTimingService::class);
        $this->scoreService = $this->createMock(MultiplayerScoreService::class);
        $this->validationService = $this->createMock(MultiplayerValidationService::class);

        $this->service = new MultiplayerGameService(
            $this->entityManager,
            $this->mercureHub,
            $this->roomRepository,
            $this->gameSessionRepository,
            $this->timingService,
            $this->scoreService,
            $this->validationService
        );
    }

    // ===== TESTS HARDCORE POUR GAGNER LES 50% ! =====

    public function testCreateRoomSuccess(): void
    {
        $creator = $this->createMock(User::class);
        $creator->method('getId')->willReturn(123);
        $creator->method('getPseudo')->willReturn('TestUser');

        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(456);

        $quizRepo = $this->createMock(QuizRepository::class);
        $quizRepo->method('find')->with(456)->willReturn($quiz);

        $this->entityManager->method('getRepository')
            ->with(Quiz::class)
            ->willReturn($quizRepo);

        $this->validationService->expects($this->once())
            ->method('validateRoomData')
            ->with(['quizId' => 456, 'maxPlayers' => 4, 'isTeamMode' => false, 'roomName' => null]);

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->createRoom($creator, 456);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('room', $result);
        $this->assertArrayHasKey('player', $result);
    }

    public function testCreateRoomWithInvalidQuizId(): void
    {
        $creator = $this->createMock(User::class);

        $this->expectException(InvalidQuestionException::class);

        $this->service->createRoom($creator, -1);
    }

    public function testCreateRoomWithNonExistentQuiz(): void
    {
        $creator = $this->createMock(User::class);

        $quizRepo = $this->createMock(QuizRepository::class);
        $quizRepo->method('find')->with(999)->willReturn(null);

        $this->entityManager->method('getRepository')
            ->with(Quiz::class)
            ->willReturn($quizRepo);

        $this->validationService->expects($this->once())
            ->method('validateRoomData');

        $this->expectException(QuizNotFoundException::class);

        $this->service->createRoom($creator, 999);
    }

    public function testCreateRoomWithCustomSettings(): void
    {
        $creator = $this->createMock(User::class);
        $creator->method('getId')->willReturn(123);
        $creator->method('getPseudo')->willReturn('TestUser');

        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(456);

        $quizRepo = $this->createMock(QuizRepository::class);
        $quizRepo->method('find')->with(456)->willReturn($quiz);

        $this->entityManager->method('getRepository')
            ->with(Quiz::class)
            ->willReturn($quizRepo);

        $this->validationService->expects($this->once())
            ->method('validateRoomData');

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->createRoom($creator, 456, 6, true, 'Ma Super Room');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('room', $result);
        $this->assertArrayHasKey('player', $result);
    }

    public function testJoinRoomSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(456);

        $room = $this->createMock(Room::class);
        $room->method('getId')->willReturn(123);
        $room->method('getStatus')->willReturn('waiting');

        $this->roomRepository->method('findOneBy')
            ->with(['roomCode' => 'ROOM123'])
            ->willReturn($room);

        $this->validationService->expects($this->once())
            ->method('validateJoinRoom')
            ->with($user, $room);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->joinRoom($user, 'ROOM123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('room', $result);
        $this->assertArrayHasKey('player', $result);
    }

    public function testJoinRoomNotFound(): void
    {
        $user = $this->createMock(User::class);

        $this->roomRepository->method('findOneBy')
            ->with(['roomCode' => 'NOTFOUND'])
            ->willReturn(null);

        $this->expectException(RoomNotFoundException::class);

        $this->service->joinRoom($user, 'NOTFOUND');
    }

    public function testStartGameSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);

        $room = $this->createMock(Room::class);
        $room->method('getId')->willReturn(456);
        $room->method('getStatus')->willReturn('waiting');
        $room->method('getCreator')->willReturn($user);

        $this->roomRepository->method('findOneBy')
            ->with(['roomCode' => 'ROOM123'])
            ->willReturn($room);

        $this->validationService->expects($this->once())
            ->method('validateGameStart')
            ->with($user, $room);

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->startGame($user, 'ROOM123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('gameSession', $result);
        $this->assertArrayHasKey('room', $result);
    }

    public function testLeaveRoomSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);

        $room = $this->createMock(Room::class);
        $room->method('getId')->willReturn(456);

        $roomPlayer = $this->createMock(RoomPlayer::class);
        $roomPlayer->method('getUser')->willReturn($user);

        $this->roomRepository->method('findOneBy')
            ->with(['roomCode' => 'ROOM123'])
            ->willReturn($room);

        $roomPlayerRepo = $this->createMock(RoomPlayerRepository::class);
        $roomPlayerRepo->method('findOneBy')
            ->with(['room' => $room, 'user' => $user])
            ->willReturn($roomPlayer);

        $this->entityManager->method('getRepository')
            ->with(RoomPlayer::class)
            ->willReturn($roomPlayerRepo);

        $this->entityManager->expects($this->once())->method('remove');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->leaveRoom($user, 'ROOM123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function testGetRoomStatusSuccess(): void
    {
        $room = $this->createMock(Room::class);
        $room->method('getId')->willReturn(123);
        $room->method('getStatus')->willReturn('waiting');

        $this->roomRepository->method('findOneBy')
            ->with(['roomCode' => 'ROOM123'])
            ->willReturn($room);

        $result = $this->service->getRoomStatus('ROOM123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('room', $result);
        $this->assertArrayHasKey('players', $result);
    }

    public function testCreateRoomWithUserWithoutPseudo(): void
    {
        $creator = $this->createMock(User::class);
        $creator->method('getId')->willReturn(123);
        $creator->method('getPseudo')->willReturn(null); // Pas de pseudo
        $creator->method('getFirstName')->willReturn('John');
        $creator->method('getLastName')->willReturn('Doe');

        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(456);

        $quizRepo = $this->createMock(QuizRepository::class);
        $quizRepo->method('find')->with(456)->willReturn($quiz);

        $this->entityManager->method('getRepository')
            ->with(Quiz::class)
            ->willReturn($quizRepo);

        $this->validationService->expects($this->once())
            ->method('validateRoomData');

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->createRoom($creator, 456);

        $this->assertIsArray($result);
    }

    public function testCreateRoomWithUserWithOnlyFirstName(): void
    {
        $creator = $this->createMock(User::class);
        $creator->method('getId')->willReturn(123);
        $creator->method('getPseudo')->willReturn(null);
        $creator->method('getFirstName')->willReturn('John');
        $creator->method('getLastName')->willReturn(null);

        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(456);

        $quizRepo = $this->createMock(QuizRepository::class);
        $quizRepo->method('find')->with(456)->willReturn($quiz);

        $this->entityManager->method('getRepository')
            ->with(Quiz::class)
            ->willReturn($quizRepo);

        $this->validationService->expects($this->once())
            ->method('validateRoomData');

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->createRoom($creator, 456);

        $this->assertIsArray($result);
    }

    public function testCreateRoomWithUserWithNoNames(): void
    {
        $creator = $this->createMock(User::class);
        $creator->method('getId')->willReturn(123);
        $creator->method('getPseudo')->willReturn(null);
        $creator->method('getFirstName')->willReturn(null);
        $creator->method('getLastName')->willReturn(null);

        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(456);

        $quizRepo = $this->createMock(QuizRepository::class);
        $quizRepo->method('find')->with(456)->willReturn($quiz);

        $this->entityManager->method('getRepository')
            ->with(Quiz::class)
            ->willReturn($quizRepo);

        $this->validationService->expects($this->once())
            ->method('validateRoomData');

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->createRoom($creator, 456);

        $this->assertIsArray($result);
    }
}
