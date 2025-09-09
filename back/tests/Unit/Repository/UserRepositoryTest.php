<?php

namespace App\Tests\Unit\Repository;

use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
    }

    public function testUserRepositoryCanBeInstantiated(): void
    {
        $repository = new UserRepository($this->managerRegistry);
        $this->assertInstanceOf(UserRepository::class, $repository);
    }

    public function testUserRepositoryHasMethods(): void
    {
        $repository = new UserRepository($this->managerRegistry);
        $this->assertTrue(method_exists($repository, 'upgradePassword'));
        $this->assertTrue(method_exists($repository, 'findActiveUsersForLeaderboard'));
        $this->assertTrue(method_exists($repository, 'findUserGameHistory'));
        $this->assertTrue(method_exists($repository, 'findUsersFromOtherCompanies'));
        $this->assertTrue(method_exists($repository, 'findAllWithStats'));
        $this->assertTrue(method_exists($repository, 'countAllWithStats'));
        $this->assertTrue(method_exists($repository, 'findByCompanyWithStats'));
        $this->assertTrue(method_exists($repository, 'findWithStats'));
        $this->assertTrue(method_exists($repository, 'findActiveUsersForMultiplayer'));
    }
}
