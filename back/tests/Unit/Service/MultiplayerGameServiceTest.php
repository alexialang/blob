<?php

namespace App\Tests\Unit\Service;

use App\Entity\GameSession;
use App\Entity\Quiz;
use App\Entity\Room;
use App\Entity\RoomPlayer;
use App\Entity\User;
use App\Repository\GameSessionRepository;
use App\Repository\RoomRepository;
use App\Service\MultiplayerGameService;
use App\Service\MultiplayerTimingService;
use App\Service\MultiplayerScoreService;
use App\Service\MultiplayerValidationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;

class MultiplayerGameServiceTest extends TestCase
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

    public function testServiceInstantiation(): void
    {
        $this->assertInstanceOf(MultiplayerGameService::class, $this->service);
    }

    public function testGetUserDisplayNameWithPseudo(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getPseudo')->willReturn('TestPseudo');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, $user);
        $this->assertEquals('TestPseudo', $result);
    }

    public function testGetUserDisplayNameWithNames(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getPseudo')->willReturn(null);
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, $user);
        $this->assertEquals('John Doe', $result);
    }

    public function testGetUserDisplayNameFallback(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getPseudo')->willReturn(null);
        $user->method('getFirstName')->willReturn(null);
        $user->method('getLastName')->willReturn(null);
        $user->method('getId')->willReturn(123);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, $user);
        $this->assertEquals('Joueur 123', $result);
    }

    public function testGetUserDisplayNameWithFirstNameOnly(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getPseudo')->willReturn(null);
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn(null);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, $user);
        $this->assertEquals('John', $result);
    }

    public function testGetUserDisplayNameWithLastNameOnly(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getPseudo')->willReturn(null);
        $user->method('getFirstName')->willReturn(null);
        $user->method('getLastName')->willReturn('Doe');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUserDisplayName');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, $user);
        $this->assertEquals('Doe', $result);
    }

    public function testStaticPropertiesInitialization(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertTrue($reflection->hasProperty('submittedAnswers'));
        $this->assertTrue($reflection->hasProperty('gameAnswers'));
        
        $submittedAnswersProperty = $reflection->getProperty('submittedAnswers');
        $submittedAnswersProperty->setAccessible(true);
        
        $gameAnswersProperty = $reflection->getProperty('gameAnswers');
        $gameAnswersProperty->setAccessible(true);
        
        // Properties should be arrays
        $this->assertIsArray($submittedAnswersProperty->getValue());
        $this->assertIsArray($gameAnswersProperty->getValue());
    }

    public function testPublicMethodsExist(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn($method) => $method->getName(), $publicMethods);
        
        // Vérifier que certaines méthodes publiques existent
        $this->assertContains('__construct', $methodNames);
        $this->assertGreaterThan(1, count($publicMethods));
    }

    public function testDependencyInjection(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        $this->assertCount(7, $constructor->getParameters());
    }

    public function testClassConstants(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Vérifier que des méthodes privées importantes existent
        $this->assertTrue($reflection->hasMethod('getUserDisplayName'));
        $this->assertNotEmpty($reflection->getProperties());
    }

    public function testGetGameTopicMethod(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getGameTopic');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'GAME123');
        $this->assertEquals('game-GAME123', $result);
    }

    public function testGetGameTopicWithDifferentCodes(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getGameTopic');
        $method->setAccessible(true);
        
        $testCodes = ['ABC123', 'XYZ789', 'TEST', ''];
        
        foreach ($testCodes as $code) {
            $result = $method->invoke($this->service, $code);
            $this->assertEquals('game-' . $code, $result);
        }
    }

    public function testCheckAnswerMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('checkAnswer'));
        
        $method = $reflection->getMethod('checkAnswer');
        $this->assertTrue($method->isPrivate());
    }

    public function testServiceHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $requiredMethods = [
            'createRoom',
            'joinRoom', 
            'leaveRoom',
            'startGame',
            'submitAnswer',
            'getRoomStatus'
        ];
        
        foreach ($requiredMethods as $methodName) {
            $this->assertTrue($reflection->hasMethod($methodName), "Method $methodName should exist");
        }
    }

    public function testServiceDependencies(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $expectedParameters = [
            'entityManager',
            'mercureHub', 
            'roomRepository',
            'gameSessionRepository',
            'timingService',
            'scoreService',
            'validationService'
        ];
        
        $this->assertCount(7, $parameters);
        
        foreach ($parameters as $index => $param) {
            $this->assertEquals($expectedParameters[$index], $param->getName());
        }
    }

    public function testStaticArraysManipulation(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $submittedAnswersProperty = $reflection->getProperty('submittedAnswers');
        $submittedAnswersProperty->setAccessible(true);
        
        $gameAnswersProperty = $reflection->getProperty('gameAnswers');
        $gameAnswersProperty->setAccessible(true);
        
        // Test setting values
        $submittedAnswersProperty->setValue(['test' => 'value']);
        $gameAnswersProperty->setValue(['game' => 'data']);
        
        $this->assertEquals(['test' => 'value'], $submittedAnswersProperty->getValue());
        $this->assertEquals(['game' => 'data'], $gameAnswersProperty->getValue());
    }
}
