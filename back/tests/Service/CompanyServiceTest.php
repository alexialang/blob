<?php

namespace App\Tests\Service;

use App\Entity\Company;
use App\Service\CompanyService;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ReflectionClass;

class CompanyServiceTest extends TestCase
{
    private CompanyService $companyService;
    private EntityManagerInterface $entityManager;
    private CompanyRepository $companyRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->companyRepository = $this->createMock(CompanyRepository::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        
        $this->companyService = new CompanyService(
            $this->entityManager,
            $this->companyRepository,
            $this->serializer,
            $this->validator
        );
    }

    private function setEntityId($entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }

    public function testList(): void
    {
        $company1 = new Company();
        $this->setEntityId($company1, 1);
        $company1->setName('Company 1');
        $company1->setDateCreation(new \DateTime());
        
        $company2 = new Company();
        $this->setEntityId($company2, 2);
        $company2->setName('Company 2');
        $company2->setDateCreation(new \DateTime());
        
        $companies = [$company1, $company2];
        
        $this->companyRepository
            ->expects($this->once())
            ->method('findAllWithRelations')
            ->willReturn($companies);

        $result = $this->companyService->list();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Company::class, $result);
    }

    public function testFind(): void
    {
        $company = new Company();
        $this->setEntityId($company, 1);
        $company->setName('Test Company');
        $company->setDateCreation(new \DateTime());
        
        $companyId = 1;
        
        $this->companyRepository
            ->expects($this->once())
            ->method('find')
            ->with($companyId)
            ->willReturn($company);

        $result = $this->companyService->find($companyId);

        $this->assertSame($company, $result);
    }

    public function testFindNotFound(): void
    {
        $companyId = 999;
        
        $this->companyRepository
            ->expects($this->once())
            ->method('find')
            ->with($companyId)
            ->willReturn(null);

        $result = $this->companyService->find($companyId);

        $this->assertNull($result);
    }

    public function testDelete(): void
    {
        $company = new Company();
        $this->setEntityId($company, 1);
        $company->setName('Test Company');
        $company->setDateCreation(new \DateTime());
        
        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($company);
            
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->companyService->delete($company);

        $this->assertTrue(true);
    }
}
