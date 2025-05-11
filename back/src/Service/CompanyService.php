<?php

namespace App\Service;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;

class CompanyService
{
    private EntityManagerInterface $em;
    private CompanyRepository $companyRepository;

    public function __construct(EntityManagerInterface $em, CompanyRepository $companyRepository)
    {
        $this->em = $em;
        $this->companyRepository = $companyRepository;
    }

    public function list(): array
    {
        return $this->companyRepository->findAll();
    }

    public function find(int $id): ?Company
    {
        return $this->companyRepository->find($id);
    }

    public function create(array $data): Company
    {
        $company = new Company();
        $company->setName($data['name']);

        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    public function update(Company $company, array $data): Company
    {
        if (isset($data['name'])) {
            $company->setName($data['name']);
        }

        $this->em->flush();

        return $company;
    }

    public function delete(Company $company): void
    {
        $this->em->remove($company);
        $this->em->flush();
    }
}
