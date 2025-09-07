<?php

namespace App\Tests\Unit\Service;

use App\Entity\Badge;
use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Service\BadgeService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class BadgeServiceTest extends TestCase
{
    private BadgeService $service;
    private EntityManagerInterface $em;
    private BadgeRepository $badgeRepository;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->badgeRepository = $this->createMock(BadgeRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new BadgeService(
            $this->em,
            $this->badgeRepository,
            $this->validator
        );
    }

    // ===== Tests pour list() =====
    
    public function testList(): void
    {
        $badges = [
            $this->createMock(Badge::class),
            $this->createMock(Badge::class)
        ];

        $this->badgeRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($badges);

        $result = $this->service->list();

        $this->assertSame($badges, $result);
        $this->assertCount(2, $result);
    }

    public function testListEmpty(): void
    {
        $this->badgeRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->service->list();

        $this->assertSame([], $result);
        $this->assertCount(0, $result);
    }

    // ===== Tests pour find() =====
    
    public function testFind(): void
    {
        $badge = $this->createMock(Badge::class);

        $this->badgeRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($badge);

        $result = $this->service->find(123);

        $this->assertSame($badge, $result);
    }

    public function testFindNotFound(): void
    {
        $this->badgeRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->find(999);

        $this->assertNull($result);
    }

    // ===== Tests pour delete() =====
    
    public function testDeleteSuccess(): void
    {
        $badge = $this->createMock(Badge::class);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($badge)
            ->willReturn($violations);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('remove')->with($badge);
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $this->service->delete($badge);
    }

    public function testDeleteValidationError(): void
    {
        $badge = $this->createMock(Badge::class);

        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $violations->method('offsetGet')->with(0)->willReturn($violation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($badge)
            ->willReturn($violations);

        $this->expectException(\Symfony\Component\Validator\Exception\ValidationFailedException::class);

        $this->service->delete($badge);
    }

    public function testDeleteException(): void
    {
        $badge = $this->createMock(Badge::class);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($badge)
            ->willReturn($violations);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('remove')->with($badge);
        $this->em->expects($this->once())->method('flush')->willThrowException(new \Exception('Database error'));
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Erreur lors de la suppression du badge');

        $this->service->delete($badge);
    }

    // ===== Tests pour awardBadge() =====
    
    public function testAwardBadgeSuccess(): void
    {
        $user = $this->createMock(User::class);
        $badge = $this->createMock(Badge::class);
        $badgeName = 'Premier Quiz';

        // Mock user badges (empty collection)
        $userBadges = new ArrayCollection([]);
        $user->method('getBadges')->willReturn($userBadges);

        // Mock badge repository
        $this->badgeRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $badgeName])
            ->willReturn($badge);

        $this->em->expects($this->once())->method('beginTransaction');
        $user->expects($this->once())->method('addBadge')->with($badge);
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $result = $this->service->awardBadge($user, $badgeName);

        $this->assertTrue($result);
    }

    public function testAwardBadgeAlreadyHasBadge(): void
    {
        $user = $this->createMock(User::class);
        $existingBadge = $this->createMock(Badge::class);
        $badgeName = 'Premier Quiz';

        // Mock existing badge
        $existingBadge->method('getName')->willReturn($badgeName);
        $userBadges = new ArrayCollection([$existingBadge]);
        $user->method('getBadges')->willReturn($userBadges);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('rollback');

        $result = $this->service->awardBadge($user, $badgeName);

        $this->assertFalse($result);
    }

    public function testAwardBadgeBadgeNotFound(): void
    {
        $user = $this->createMock(User::class);
        $badgeName = 'Nonexistent Badge';

        // Mock user badges (empty collection)
        $userBadges = new ArrayCollection([]);
        $user->method('getBadges')->willReturn($userBadges);

        // Mock badge repository - badge not found
        $this->badgeRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $badgeName])
            ->willReturn(null);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('rollback');

        $result = $this->service->awardBadge($user, $badgeName);

        $this->assertFalse($result);
    }

    public function testAwardBadgeException(): void
    {
        $user = $this->createMock(User::class);
        $badge = $this->createMock(Badge::class);
        $badgeName = 'Premier Quiz';

        // Mock user badges (empty collection)
        $userBadges = new ArrayCollection([]);
        $user->method('getBadges')->willReturn($userBadges);

        // Mock badge repository
        $this->badgeRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $badgeName])
            ->willReturn($badge);

        $this->em->expects($this->once())->method('beginTransaction');
        $user->expects($this->once())->method('addBadge')->with($badge);
        $this->em->expects($this->once())->method('flush')->willThrowException(new \Exception('Database error'));
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Erreur lors de l\'attribution du badge');

        $this->service->awardBadge($user, $badgeName);
    }

    public function testAwardBadgeEmptyName(): void
    {
        $user = $this->createMock(User::class);
        $badgeName = '';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du badge ne peut pas être vide');

        $this->service->awardBadge($user, $badgeName);
    }

    public function testAwardBadgeNameTooLong(): void
    {
        $user = $this->createMock(User::class);
        $badgeName = str_repeat('a', 101); // 101 characters

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du badge ne peut pas dépasser 100 caractères');

        $this->service->awardBadge($user, $badgeName);
    }

    // ===== Tests pour initializeBadges() =====
    
    public function testInitializeBadgesSuccess(): void
    {
        // Mock validation for badge data
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->exactly(5)) // 5 default badges
            ->method('validate')
            ->willReturn($violations);

        // Mock repository - no existing badges
        $this->badgeRepository->expects($this->exactly(5))
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->exactly(5))->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $this->service->initializeBadges();
    }

    public function testInitializeBadgesWithExistingBadges(): void
    {
        $existingBadge = $this->createMock(Badge::class);

        // Mock validation for badge data
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(0);

        $this->validator->expects($this->exactly(5)) // 5 default badges
            ->method('validate')
            ->willReturn($violations);

        // Mock repository - some badges already exist
        $this->badgeRepository->expects($this->exactly(5))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls($existingBadge, null, null, null, null);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->exactly(4))->method('persist'); // Only 4 new badges
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $this->service->initializeBadges();
    }

    public function testInitializeBadgesException(): void
    {
        // Mock validation for badge data - first validation throws exception
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class);
        $violations->method('offsetGet')->with(0)->willReturn($violation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('rollback');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Erreur lors de l\'initialisation des badges');

        $this->service->initializeBadges();
    }
}
