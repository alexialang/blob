<?php

namespace App\Tests\Unit\Repository;

use App\Repository\TypeQuestionRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class TypeQuestionRepositoryTest extends TestCase
{
    public function testRepositoryCreation(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new TypeQuestionRepository($managerRegistry);

        $this->assertInstanceOf(TypeQuestionRepository::class, $repository);
    }

    public function testEntityClass(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new TypeQuestionRepository($managerRegistry);

        $reflection = new \ReflectionClass($repository);
        $this->assertTrue($reflection->isSubclassOf('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository'));
    }
}
