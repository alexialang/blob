<?php

namespace App\Tests\Unit\Repository;

use App\Repository\GroupRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class GroupRepositoryTest extends TestCase
{
    public function testGroupRepositoryCanBeInstantiated(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new GroupRepository($managerRegistry);
        $this->assertInstanceOf(GroupRepository::class, $repository);
    }

    public function testGroupRepositoryHasMethods(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new GroupRepository($managerRegistry);
        $this->assertTrue(method_exists($repository, 'findByCompany'));
        $this->assertTrue(method_exists($repository, 'isUserInGroup'));
    }
}
