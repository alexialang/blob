<?php

namespace App\Tests\Unit\Repository;

use App\Repository\CompanyRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class CompanyRepositoryTest extends TestCase
{
    public function testCompanyRepositoryCanBeInstantiated(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new CompanyRepository($managerRegistry);
        $this->assertInstanceOf(CompanyRepository::class, $repository);
    }

    public function testCompanyRepositoryHasMethods(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new CompanyRepository($managerRegistry);
        $this->assertTrue(method_exists($repository, 'findByUserAdmin'));
        $this->assertTrue(method_exists($repository, 'findGroupInCompany'));
        $this->assertTrue(method_exists($repository, 'findAllWithRelations'));
        $this->assertTrue(method_exists($repository, 'findByIdWithRelations'));
        $this->assertTrue(method_exists($repository, 'findGroupsWithUsersByCompany'));
        $this->assertTrue(method_exists($repository, 'isUserInGroup'));
    }
}
