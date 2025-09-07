<?php

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UserRepositoryUltimateTest extends TestCase
{
    private UserRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = new UserRepository($this->entityManager);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(UserRepository::class, $this->repository);
    }

    public function testGetEntityClass(): void
    {
        $this->assertEquals(User::class, $this->repository->getEntityName());
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
