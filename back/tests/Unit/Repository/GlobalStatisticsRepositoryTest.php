<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Quiz;
use App\Repository\GlobalStatisticsRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class GlobalStatisticsRepositoryTest extends TestCase
{
    public function testRepositoryCreation(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new GlobalStatisticsRepository($managerRegistry);
        
        $this->assertInstanceOf(GlobalStatisticsRepository::class, $repository);
    }
    
    public function testEntityClass(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new GlobalStatisticsRepository($managerRegistry);
        
        $reflection = new \ReflectionClass($repository);
        $this->assertTrue($reflection->isSubclassOf('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository'));
    }
}

