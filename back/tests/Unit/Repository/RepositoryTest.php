<?php

namespace App\Tests\Unit\Repository;

use App\Repository\GroupRepository;
use App\Repository\UserAnswerRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    public function testGroupRepositoryConstructor(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new GroupRepository($registry);
        $this->assertInstanceOf(GroupRepository::class, $repository);
    }

    public function testUserAnswerRepositoryConstructor(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new UserAnswerRepository($registry);
        $this->assertInstanceOf(UserAnswerRepository::class, $repository);
    }
}



