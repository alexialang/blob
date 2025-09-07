<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CompanyRepositoryBasicTest extends TestCase
{
    private CompanyRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = new CompanyRepository($this->entityManager);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(CompanyRepository::class, $this->repository);
    }

    public function testGetEntityClass(): void
    {
        $this->assertEquals(Company::class, $this->repository->getEntityName());
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class, $this->repository);
    }
}
