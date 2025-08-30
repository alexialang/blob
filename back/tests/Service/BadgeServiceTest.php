<?php

namespace App\Tests\Service;

use App\Entity\Badge;
use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Repository\UserRepository;
use App\Service\BadgeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BadgeServiceTest extends TestCase
{
    private BadgeService $badgeService;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|BadgeRepository $badgeRepository;
    private MockObject|UserRepository $userRepository;
    private MockObject|ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->badgeRepository = $this->createMock(BadgeRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        
        $this->badgeService = new BadgeService(
            $this->entityManager,
            $this->badgeRepository,
            $this->userRepository,
            $this->validator
        );
    }

    public function testList(): void
    {
        $badges = [new Badge(), new Badge()];
        
        $this->badgeRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($badges);

        $result = $this->badgeService->list();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Badge::class, $result);
    }

    public function testFind(): void
    {
        $badge = new Badge();
        $badgeId = 1;
        
        $this->badgeRepository
            ->expects($this->once())
            ->method('find')
            ->with($badgeId)
            ->willReturn($badge);

        $result = $this->badgeService->find($badgeId);

        $this->assertSame($badge, $result);
    }

    public function testFindNotFound(): void
    {
        $badgeId = 999;
        
        $this->badgeRepository
            ->expects($this->once())
            ->method('find')
            ->with($badgeId)
            ->willReturn(null);

        $result = $this->badgeService->find($badgeId);

        $this->assertNull($result);
    }

    public function testDelete(): void
    {
        $badge = new Badge();
        
        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($badge);
            
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->badgeService->delete($badge);

        $this->assertTrue(true);
    }
}
