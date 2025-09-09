<?php

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class UserRepositoryUltimateTest extends TestCase
{
    private UserRepository $repository;
    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = new UserRepository($this->registry);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(UserRepository::class, $this->repository);
    }


    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCustomMethods(): void
    {
        $this->assertTrue(method_exists($this->repository, 'findUserGameHistory'));
        $this->assertTrue(method_exists($this->repository, 'findUsersFromOtherCompanies'));
        $this->assertTrue(method_exists($this->repository, 'findActiveUsersForMultiplayer'));
    }
}
