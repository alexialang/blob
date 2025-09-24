<?php

namespace App\Tests\Unit\Service;

use App\Entity\Quiz;
use App\Entity\Room;
use App\Entity\RoomPlayer;
use App\Entity\User;
use App\Exception\InvalidQuestionException;
use App\Exception\QuizNotFoundException;
use App\Exception\RoomNotFoundException;
use App\Repository\GameSessionRepository;
use App\Repository\QuizRepository;
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
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('quiz', $result);
        $this->assertArrayHasKey('players', $result);
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
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('quiz', $result);
        $this->assertArrayHasKey('players', $result);
    }

    public function testJoinRoomSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(456);

        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(789);
        $quiz->method('getTitle')->willReturn('Test Quiz');
        $quiz->method('getQuestionCount')->willReturn(5);
        $quiz->method('getQuestions')->willReturn($this->createMock(\Doctrine\Common\Collections\Collection::class));

        $room = $this->createMock(Room::class);
        $room->method('getId')->willReturn(123);
        $room->method('getRoomCode')->willReturn('ROOM123');
        $room->method('getStatus')->willReturn('waiting');
        $room->method('getCurrentPlayerCount')->willReturn(1);
        $room->method('getMaxPlayers')->willReturn(4);
        $room->method('getQuiz')->willReturn($quiz);
        $room->method('getCreator')->willReturn($user);
        $room->method('isTeamMode')->willReturn(false);
        $room->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $room->method('getGameStartedAt')->willReturn(null);
        $room->method('getGameSession')->willReturn(null);
        $room->method('getPlayers')->willReturn($this->createMock(\Doctrine\Common\Collections\Collection::class));

        $this->roomRepository->method('findByRoomCode')
            ->with('ROOM123')
            ->willReturn($room);

        $this->validationService->expects($this->once())
            ->method('validateJoinRoomData')
            ->with(['teamName' => null]);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->joinRoom('ROOM123', $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('quiz', $result);
        $this->assertArrayHasKey('players', $result);
    }

    public function testJoinRoomNotFound(): void
    {
        $user = $this->createMock(User::class);

        $this->roomRepository->method('findOneBy')
            ->with(['roomCode' => 'NOTFOUND'])
            ->willReturn(null);

        $this->expectException(RoomNotFoundException::class);

        $this->service->joinRoom('NOTFOUND', $user);
    }

    public function testStartGameSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);

        $player = $this->createMock(RoomPlayer::class);
        $player->method('getUser')->willReturn($user);
        $player->method('isCreator')->willReturn(true);

        $typeQuestion = $this->createMock(\App\Entity\TypeQuestion::class);
        $typeQuestion->method('getName')->willReturn('multiple_choice');

        $answer = $this->createMock(\App\Entity\Answer::class);
        $answer->method('getId')->willReturn(1);
        $answer->method('getAnswer')->willReturn('Test Answer');
        $answer->method('getPairId')->willReturn(null);
        $answer->method('getOrderCorrect')->willReturn('1');

        $answersCollection = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $answersCollection->method('toArray')->willReturn([$answer]);

        $question = $this->createMock(\App\Entity\Question::class);
        $question->method('getId')->willReturn(1);
        $question->method('getQuestion')->willReturn('Test Question');
        $question->method('getTypeQuestion')->willReturn($typeQuestion);
        $question->method('getAnswers')->willReturn($answersCollection);

        $questionsCollection = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $questionsCollection->method('toArray')->willReturn([$question]);

        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(789);
        $quiz->method('getTitle')->willReturn('Test Quiz');
        $quiz->method('getQuestionCount')->willReturn(5);
        $quiz->method('getQuestions')->willReturn($questionsCollection);

        $room = $this->createMock(Room::class);
        $room->method('getId')->willReturn(456);
        $room->method('getRoomCode')->willReturn('ROOM123');
        $room->method('getStatus')->willReturn('waiting');
        $room->method('getQuiz')->willReturn($quiz);
        $room->method('getCreator')->willReturn($user);
        $room->method('getMaxPlayers')->willReturn(4);
        $room->method('isTeamMode')->willReturn(false);
        $room->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $room->method('getGameStartedAt')->willReturn(null);
        $room->method('getGameSession')->willReturn(null);
        $collection = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$player]));
        $collection->method('toArray')->willReturn([$player]);
        $collection->method('first')->willReturn($player);
        $collection->method('exists')->willReturn(true);
        $room->method('getPlayers')->willReturn($collection);
        $room->method('getCurrentPlayerCount')->willReturn(2);

        $this->roomRepository->method('findByRoomCode')
            ->with('ROOM123')
            ->willReturn($room);

        $this->validationService->expects($this->never())
            ->method('validateRoomData');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->startGame('ROOM123', $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('quiz', $result);
        $this->assertArrayHasKey('players', $result);
    }

    public function testLeaveRoomSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);

        $roomPlayer = $this->createMock(RoomPlayer::class);
        $roomPlayer->method('getUser')->willReturn($user);
        $roomPlayer->method('isCreator')->willReturn(false);

        $collection = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$roomPlayer]));

        $room = $this->createMock(Room::class);
        $room->method('getId')->willReturn(456);
        $room->method('getPlayers')->willReturn($collection);
        $room->method('removePlayer')->willReturnSelf();

        $this->roomRepository->method('findByRoomCode')
            ->with('ROOM123')
            ->willReturn($room);

        $this->entityManager->expects($this->exactly(2))->method('remove');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->leaveRoom('ROOM123', $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('deleted', $result);
        $this->assertTrue($result['deleted']);
    }

    public function testGetRoomStatusSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);

        $player = $this->createMock(RoomPlayer::class);
        $player->method('getUser')->willReturn($user);
        $player->method('isCreator')->willReturn(true);
        $player->method('getTeam')->willReturn(null);

        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getId')->willReturn(789);
        $quiz->method('getTitle')->willReturn('Test Quiz');
        $quiz->method('getQuestionCount')->willReturn(5);
        $quiz->method('getQuestions')->willReturn($this->createMock(\Doctrine\Common\Collections\Collection::class));

        $collection = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$player]));
        $collection->method('toArray')->willReturn([$player]);
        $collection->method('first')->willReturn($player);
        $collection->method('exists')->willReturn(true);

        $room = $this->createMock(Room::class);
        $room->method('getId')->willReturn(123);
        $room->method('getRoomCode')->willReturn('ROOM123');
        $room->method('getStatus')->willReturn('waiting');
        $room->method('getQuiz')->willReturn($quiz);
        $room->method('getCreator')->willReturn($user);
        $room->method('getMaxPlayers')->willReturn(4);
        $room->method('isTeamMode')->willReturn(false);
        $room->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $room->method('getGameStartedAt')->willReturn(null);
        $room->method('getGameSession')->willReturn(null);
        $room->method('getPlayers')->willReturn($collection);
        $room->method('getCurrentPlayerCount')->willReturn(1);

        $this->roomRepository->method('findByRoomCode')
            ->with('ROOM123')
            ->willReturn($room);

        $result = $this->service->getRoomStatus('ROOM123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('quiz', $result);
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
