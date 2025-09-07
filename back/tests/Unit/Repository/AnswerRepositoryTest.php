<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Answer;
use App\Repository\AnswerRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class AnswerRepositoryTest extends TestCase
{
    public function testRepositoryCreation(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new AnswerRepository($managerRegistry);
        
        $this->assertInstanceOf(AnswerRepository::class, $repository);
    }
    
    public function testEntityClass(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new AnswerRepository($managerRegistry);
        
        $reflection = new \ReflectionClass($repository);
        $this->assertTrue($reflection->isSubclassOf('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository'));
    }
}

