<?php

namespace App\Tests\Unit\Repository;

use App\Repository\CategoryQuizRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class CategoryQuizRepositoryTest extends TestCase
{
    public function testRepositoryCreation(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new CategoryQuizRepository($managerRegistry);

        $this->assertInstanceOf(CategoryQuizRepository::class, $repository);
    }

    public function testEntityClass(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new CategoryQuizRepository($managerRegistry);

        $reflection = new \ReflectionClass($repository);
        $this->assertTrue($reflection->isSubclassOf('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository'));
    }
}
