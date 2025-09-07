<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class RoomRepositoryTest extends TestCase
{
    public function testRepositoryCreation(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new RoomRepository($managerRegistry);
        
        $this->assertInstanceOf(RoomRepository::class, $repository);
    }
    
    public function testEntityClass(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new RoomRepository($managerRegistry);
        
        $reflection = new \ReflectionClass($repository);
        $this->assertTrue($reflection->isSubclassOf('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository'));
    }
}

