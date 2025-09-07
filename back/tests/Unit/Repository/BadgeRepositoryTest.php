<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Badge;
use App\Repository\BadgeRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class BadgeRepositoryTest extends TestCase
{
    public function testRepositoryCreation(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new BadgeRepository($managerRegistry);
        
        $this->assertInstanceOf(BadgeRepository::class, $repository);
    }
    
    public function testEntityClass(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new BadgeRepository($managerRegistry);
        
        $reflection = new \ReflectionClass($repository);
        $this->assertTrue($reflection->isSubclassOf('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository'));
    }
}

