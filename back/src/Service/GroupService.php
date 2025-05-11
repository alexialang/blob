<?php

namespace App\Service;

use App\Entity\Group;
use App\Service\CompanyService;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;

class GroupService
{
    private EntityManagerInterface $em;
    private GroupRepository $groupRepository;
    private CompanyService $companyService;

    public function __construct(EntityManagerInterface $em, GroupRepository $groupRepository, CompanyService $companyService)
    {
        $this->em = $em;
        $this->groupRepository = $groupRepository;
        $this->companyService = $companyService;
    }

    public function list(): array
    {
        return $this->groupRepository->findAll();
    }

    public function find(int $id): ?Group
    {
        return $this->groupRepository->find($id);
    }

    public function create(array $data): Group
    {
        $group = new Group();
        $group->setName($data['name']);
        $group->setAccesCode($data['acces_code'] ?? null);

        if (isset($data['company_id'])) {
            $company = $this->companyService->find($data['company_id']);
            $group->setCompany($company);
        }

        $this->em->persist($group);
        $this->em->flush();

        return $group;
    }

    public function update(Group $group, array $data): Group
    {
        if (isset($data['name'])) {
            $group->setName($data['name']);
        }
        if (isset($data['acces_code'])) {
            $group->setAccesCode($data['acces_code']);
        }
        if (isset($data['company_id'])) {
            $company = $this->companyService->find($data['company_id']);
            $group->setCompany($company);
        }

        $this->em->flush();

        return $group;
    }

    public function delete(Group $group): void
    {
        $this->em->remove($group);
        $this->em->flush();
    }
}
