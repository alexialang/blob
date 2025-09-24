<?php

namespace App\Tests\Unit\Service;

use App\Entity\GameSession;
use App\Service\MultiplayerTimingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MultiplayerTimingServiceTest extends TestCase
{
    private MultiplayerTimingService $service;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new MultiplayerTimingService($this->entityManager);
    }

    public function testSetupQuestionTiming(): void
    {
        $gameSession = $this->createMock(GameSession::class);

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionStartedAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class));

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionDuration')
            ->with(30);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->setupQuestionTiming($gameSession);
    }

    public function testSetupQuestionTimingWithCustomDuration(): void
    {
        $gameSession = $this->createMock(GameSession::class);

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionStartedAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class));

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionDuration')
            ->with(60);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->setupQuestionTiming($gameSession, 60);
    }

    public function testCalculateTimeLeftWithNoStartTime(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn(null);
        $gameSession->method('getCurrentQuestionDuration')->willReturn(30);

        $result = $this->service->calculateTimeLeft($gameSession);

        $this->assertEquals(30, $result);
    }

    public function testCalculateTimeLeftWithNoDuration(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $startTime = new \DateTimeImmutable();
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn($startTime);
        $gameSession->method('getCurrentQuestionDuration')->willReturn(null);

        $result = $this->service->calculateTimeLeft($gameSession);

        $this->assertEquals(30, $result);
    }

    public function testCalculateTimeLeftWithTimeRemaining(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $startTime = new \DateTimeImmutable('-10 seconds'); // 10 secondes passées
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn($startTime);
        $gameSession->method('getCurrentQuestionDuration')->willReturn(30);

        $result = $this->service->calculateTimeLeft($gameSession);

        $this->assertGreaterThanOrEqual(15, $result);
        $this->assertLessThanOrEqual(25, $result); // Tolérance pour le timing
    }

    public function testCalculateTimeLeftWithTimeExpired(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $startTime = new \DateTimeImmutable('-40 seconds'); // 40 secondes passées
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn($startTime);
        $gameSession->method('getCurrentQuestionDuration')->willReturn(30);

        $result = $this->service->calculateTimeLeft($gameSession);

        $this->assertEquals(0, $result);
    }

    public function testIsTimeExpiredTrue(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $startTime = new \DateTimeImmutable('-40 seconds');
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn($startTime);
        $gameSession->method('getCurrentQuestionDuration')->willReturn(30);

        $result = $this->service->isTimeExpired($gameSession);

        $this->assertTrue($result);
    }

    public function testIsTimeExpiredFalse(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $startTime = new \DateTimeImmutable('-10 seconds');
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn($startTime);
        $gameSession->method('getCurrentQuestionDuration')->willReturn(30);

        $result = $this->service->isTimeExpired($gameSession);

        $this->assertFalse($result);
    }

    public function testEnsureTimingExistsWhenMissing(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn(null);
        $gameSession->method('getCurrentQuestionDuration')->willReturn(null);

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionStartedAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class));

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionDuration')
            ->with(30);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->ensureTimingExists($gameSession);
    }

    public function testEnsureTimingExistsWhenPresent(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn(new \DateTimeImmutable());
        $gameSession->method('getCurrentQuestionDuration')->willReturn(30);

        $gameSession->expects($this->never())
            ->method('setCurrentQuestionStartedAt');

        $gameSession->expects($this->never())
            ->method('setCurrentQuestionDuration');

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->service->ensureTimingExists($gameSession);
    }

    public function testCheckTransitionCooldownWithNoStartTime(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn(null);

        $result = $this->service->checkTransitionCooldown($gameSession);

        $this->assertTrue($result);
    }

    public function testCheckTransitionCooldownWithCooldownActive(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $recentTime = new \DateTimeImmutable('-1 second');
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn($recentTime);

        $result = $this->service->checkTransitionCooldown($gameSession, 3);

        $this->assertFalse($result);
    }

    public function testCheckTransitionCooldownWithCooldownExpired(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $oldTime = new \DateTimeImmutable('-5 seconds');
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn($oldTime);

        $result = $this->service->checkTransitionCooldown($gameSession, 3);

        $this->assertTrue($result);
    }

    public function testSetupQuestionTimingWithZeroDuration(): void
    {
        $gameSession = $this->createMock(GameSession::class);

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionStartedAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class));

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionDuration')
            ->with(0);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->setupQuestionTiming($gameSession, 0);
    }

    public function testCalculateTimeLeftWithExactTimeMatch(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $startTime = new \DateTimeImmutable('-30 seconds'); // Exactement 30 secondes
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn($startTime);
        $gameSession->method('getCurrentQuestionDuration')->willReturn(30);

        $result = $this->service->calculateTimeLeft($gameSession);

        $this->assertEquals(0, $result); // Temps exactement écoulé
    }

    public function testEnsureTimingExistsWithOnlyStartTimeMissing(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn(null);
        $gameSession->method('getCurrentQuestionDuration')->willReturn(45); // Duration présente

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionStartedAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class));

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionDuration')
            ->with(30);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->ensureTimingExists($gameSession);
    }

    public function testEnsureTimingExistsWithOnlyDurationMissing(): void
    {
        $gameSession = $this->createMock(GameSession::class);
        $gameSession->method('getCurrentQuestionStartedAt')->willReturn(new \DateTimeImmutable());
        $gameSession->method('getCurrentQuestionDuration')->willReturn(null); // Duration manquante

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionStartedAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class));

        $gameSession->expects($this->once())
            ->method('setCurrentQuestionDuration')
            ->with(30);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->ensureTimingExists($gameSession);
    }
}
