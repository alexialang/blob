<?php

namespace App\Tests\Unit\Repository;

use App\Repository\UserPermissionRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class UserPermissionRepositoryTest extends TestCase
{
    public function testRepositoryCreation(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new UserPermissionRepository($managerRegistry);

        $this->assertInstanceOf(UserPermissionRepository::class, $repository);
    }

    public function testEntityClass(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new UserPermissionRepository($managerRegistry);

        $reflection = new \ReflectionClass($repository);
        $this->assertTrue($reflection->isSubclassOf('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository'));
    }
}
