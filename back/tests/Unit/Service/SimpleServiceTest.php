<?php

namespace App\Tests\Unit\Service;

use App\Repository\GameSessionRepository;
use App\Repository\RoomRepository;
use App\Service\MultiplayerGameService;
use App\Service\MultiplayerScoreService;
use App\Service\MultiplayerTimingService;
use App\Service\MultiplayerValidationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;

class SimpleServiceTest extends TestCase
{
    public function testMultiplayerGameServiceConstructor(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $hub = $this->createMock(HubInterface::class);
        $roomRepo = $this->createMock(RoomRepository::class);
        $gameRepo = $this->createMock(GameSessionRepository::class);
        $timing = $this->createMock(MultiplayerTimingService::class);
        $score = $this->createMock(MultiplayerScoreService::class);
        $validation = $this->createMock(MultiplayerValidationService::class);

        $service = new MultiplayerGameService($em, $hub, $roomRepo, $gameRepo, $timing, $score, $validation);

        $this->assertInstanceOf(MultiplayerGameService::class, $service);
    }

    public function testMultiplayerScoreServiceConstructor(): void
    {
        $service = new MultiplayerScoreService();
        $this->assertInstanceOf(MultiplayerScoreService::class, $service);
    }

    public function testMultiplayerTimingServiceConstructor(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $service = new MultiplayerTimingService($em);
        $this->assertInstanceOf(MultiplayerTimingService::class, $service);
    }

    public function testMultiplayerValidationServiceConstructor(): void
    {
        $validator = $this->createMock(\Symfony\Component\Validator\Validator\ValidatorInterface::class);
        $service = new MultiplayerValidationService($validator);
        $this->assertInstanceOf(MultiplayerValidationService::class, $service);
    }
}
