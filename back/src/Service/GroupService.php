<?php

namespace App\Service;

use App\Entity\Group;
use App\Entity\User;
use App\Entity\Company;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class GroupService
{
    private EntityManagerInterface $em;
    private GroupRepository $groupRepository;
    private CompanyService $companyService;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $em, GroupRepository $groupRepository, CompanyService $companyService, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->groupRepository = $groupRepository;
        $this->companyService = $companyService;
        $this->validator = $validator;
    }

    public function list(): array
    {
        return $this->groupRepository->findAll();
    }

    public function getGroupsByUser(User $user): array
    {
        $companyId = $user->getCompanyId();
        
        if (!$companyId) {
            return [];
        }
        
        $company = $this->em->getRepository(Company::class)->find($companyId);
        if (!$company) {
            return [];
        }
        
        return $this->groupRepository->findBy(['company' => $company]);
    }

    public function find(int $id): ?Group
    {
        return $this->groupRepository->find($id);
    }

    public function create(array $data): Group
    {
        $this->validateGroupData($data);
        
        $group = new Group();
        $group->setName($data['name']);
        $group->setAccesCode($data['acces_code'] ?? '');

        if (isset($data['company_id'])) {
            $company = $this->em->getRepository(Company::class)->find($data['company_id']);
            if ($company) {
                $group->setCompany($company);
            }
        }

        $this->em->persist($group);
        $this->em->flush();

        if (isset($data['member_ids']) && is_array($data['member_ids'])) {
            foreach ($data['member_ids'] as $userId) {
                $user = $this->em->getRepository(User::class)->find($userId);
                if ($user) {
                    $this->addUserToGroup($group, $user);
                }
            }
        }

        return $group;
    }

    public function update(Group $group, array $data): Group
    {
        $this->validateGroupData($data);
        
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

    public function addUserToGroup(Group $group, User $user): void
    {
        if (!$group->getUsers()->contains($user)) {
            $group->addUser($user);
            $this->em->flush();
        }
    }

    public function removeUserFromGroup(Group $group, User $user): void
    {
        if ($group->getUsers()->contains($user)) {
            $group->removeUser($user);
            $this->em->flush();
        }
    }

    private function validateGroupData(array $data): void
    {
        $constraints = new Assert\Collection([
            'fields' => [
                'name' => [
                    new Assert\NotBlank(['message' => 'Le nom du groupe est requis']),
                    new Assert\Length(['max' => 100, 'maxMessage' => 'Le nom ne peut pas dépasser 100 caractères'])
                ],
                'description' => [
                    new Assert\Optional([
                        new Assert\Length(['max' => 500, 'maxMessage' => 'La description ne peut pas dépasser 500 caractères'])
                    ])
                ],
                'acces_code' => [
                    new Assert\Optional([
                        new Assert\Length(['max' => 50, 'maxMessage' => 'Le code d\'accès ne peut pas dépasser 50 caractères'])
                    ])
                ],
                'company_id' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'integer', 'message' => 'L\'ID de l\'entreprise doit être un entier'])
                    ])
                ],
                'member_ids' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'array', 'message' => 'Les IDs des membres doivent être un tableau'])
                    ])
                ]
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }
}
