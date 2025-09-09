<?php

namespace App\Tests\Unit\Repository;

use App\Repository\CompanyRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class CompanyRepositoryBasicTest extends TestCase
{
    private CompanyRepository $repository;
    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = new CompanyRepository($this->registry);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(CompanyRepository::class, $this->repository);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class, $this->repository);
    }
}
