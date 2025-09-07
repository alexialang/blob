<?php

namespace App\Tests\Unit\Service;

use App\Entity\Company;
use App\Entity\User;
use App\Repository\CompanyRepository;
use App\Service\CompanyService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CompanyServiceSimpleTest extends TestCase
{
    private CompanyService $service;
    private CompanyRepository $companyRepository;
    private EntityManagerInterface $em;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->companyRepository = $this->createMock(CompanyRepository::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        
        $this->service = new CompanyService(
            $this->em,
            $this->companyRepository,
            $this->serializer,
            $this->validator
        );
    }

    public function testList(): void
    {
        $companies = [
            $this->createMock(Company::class),
            $this->createMock(Company::class)
        ];

        $this->companyRepository->expects($this->once())
            ->method('findAllWithRelations')
            ->willReturn($companies);

        $result = $this->service->list();

        $this->assertSame($companies, $result);
        $this->assertCount(2, $result);
    }

    public function testFindByUserWithCompany(): void
    {
        $company = $this->createMock(Company::class);
        $user = $this->createMock(User::class);
        $user->method('getCompany')->willReturn($company);

        $result = $this->service->findByUser($user);

        $this->assertSame([$company], $result);
        $this->assertCount(1, $result);
    }

    public function testFindByUserWithoutCompany(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCompany')->willReturn(null);

        $result = $this->service->findByUser($user);

        $this->assertSame([], $result);
        $this->assertCount(0, $result);
    }

    public function testFind(): void
    {
        $company = $this->createMock(Company::class);

        $this->companyRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($company);

        $result = $this->service->find(123);

        $this->assertSame($company, $result);
    }

    public function testFindNotFound(): void
    {
        $this->companyRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->find(999);

        $this->assertNull($result);
    }

    public function testListEmpty(): void
    {
        $this->companyRepository->expects($this->once())
            ->method('findAllWithRelations')
            ->willReturn([]);

        $result = $this->service->list();

        $this->assertSame([], $result);
        $this->assertEmpty($result);
    }
}
