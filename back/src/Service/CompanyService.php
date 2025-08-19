<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\User;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CompanyService
{
    private EntityManagerInterface $em;
    private CompanyRepository $companyRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $em,
        CompanyRepository $companyRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->em = $em;
        $this->companyRepository = $companyRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    public function list(): array
    {
        return $this->companyRepository->findAll();
    }

    public function findByUser(User $user): array
    {
        if ($user->getCompany()) {
            return [$user->getCompany()];
        }
        return [];
    }

    public function countAll(): int
    {
        return $this->companyRepository->count([]);
    }

    public function countByUser(User $user): int
    {
        $companies = $this->findByUser($user);
        return count($companies);
    }

    public function find(int $id): ?Company
    {
        return $this->companyRepository->find($id);
    }

    public function create(array $data): Company
    {
        $company = new Company();
        $company->setName($data['name']);
        $company->setDateCreation(new \DateTime());

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
        foreach ($company->getUsers() as $user) {
            $user->setCompany(null);
        }
        
        foreach ($company->getGroups() as $group) {
            $group->setCompany(null);
        }
        
        foreach ($company->getQuizs() as $quiz) {
            $quiz->setCompany(null);
        }
        
        $company->getUsers()->clear();
        $company->getGroups()->clear();
        $company->getQuizs()->clear();
        
        $this->em->remove($company);
        $this->em->flush();
    }

    public function exportCompaniesToCsv(): string
    {
        $companies = $this->companyRepository->findAll();
        
        $csv = "ID,Nom,Nombre d'utilisateurs,Nombre de groupes,Nombre de quiz,Date de création\n";
        
        foreach ($companies as $company) {
            $userCount = $company->getUsers()->count();
            $groupCount = $company->getGroups()->count();
            $quizCount = $company->getQuizs()->count();
            $createdAt = $company->getDateCreation() ? $company->getDateCreation()->format('Y-m-d') : 'N/A';
            
            $csv .= sprintf(
                "%d,%s,%d,%d,%d,%s\n",
                $company->getId(),
                $company->getName(),
                $userCount,
                $groupCount,
                $quizCount,
                $createdAt
            );
        }
        
        return $csv;
    }

    public function exportCompaniesToJson(): string
    {
        $companies = $this->companyRepository->findAll();
        
        $data = [];
        foreach ($companies as $company) {
            $data[] = [
                'id' => $company->getId(),
                'name' => $company->getName(),
                'userCount' => $company->getUsers()->count(),
                'groupCount' => $company->getGroups()->count(),
                'quizCount' => $company->getQuizs()->count(),
                'createdAt' => $company->getDateCreation() ? $company->getDateCreation()->format('Y-m-d H:i:s') : null,
                'users' => $company->getUsers()->map(fn($user) => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'roles' => $user->getRoles()
                ])->toArray(),
                'groups' => $company->getGroups()->map(fn($group) => [
                    'id' => $group->getId(),
                    'name' => $group->getName()
                ])->toArray()
            ];
        }
        
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function importCompaniesFromCsv(UploadedFile $file): array
    {
        $content = file_get_contents($file->getPathname());
        $lines = explode("\n", trim($content));
        
        $results = ['success' => 0, 'errors' => []];
        
        array_shift($lines);
        
        foreach ($lines as $lineNumber => $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            
            if (count($data) < 1) {
                $results['errors'][] = "Ligne " . ($lineNumber + 2) . ": Format invalide";
                continue;
            }
            
            try {
                $company = new Company();
                $company->setName(trim($data[0]));
                
                $errors = $this->validator->validate($company);
                if (count($errors) > 0) {
                    $results['errors'][] = "Ligne " . ($lineNumber + 2) . ": " . $errors[0]->getMessage();
                    continue;
                }
                
                $this->em->persist($company);
                $results['success']++;
                
            } catch (\Exception $e) {
                $results['errors'][] = "Ligne " . ($lineNumber + 2) . ": " . $e->getMessage();
            }
        }
        
        if ($results['success'] > 0) {
            $this->em->flush();
        }
        
        return $results;
    }

    public function getCompanyStats(Company $company): array
    {
        return [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'userCount' => $company->getUsers()->count(),
            'activeUsers' => $company->getUsers()->filter(fn($user) => $user->isIsActive())->count(),
            'groupCount' => $company->getGroups()->count(),
            'quizCount' => $company->getQuizs()->count(),
            'createdAt' => $company->getDateCreation() ? $company->getDateCreation()->format('Y-m-d H:i:s') : null,
            'lastActivity' => $this->getLastActivity($company)
        ];
    }

    private function getLastActivity(Company $company): ?string
    {
        $lastActivity = null;
        
        foreach ($company->getUsers() as $user) {
            if ($user->getLastAccess()) {
                $accessTime = $user->getLastAccess()->format('Y-m-d H:i:s');
                if (!$lastActivity || $accessTime > $lastActivity) {
                    $lastActivity = $accessTime;
                }
            }
        }
        
        return $lastActivity;
    }

    public function assignUserToCompany(int $userId, int $companyId, array $roles = ['ROLE_USER'], array $permissions = []): array
    {
        $user = $this->em->getRepository(\App\Entity\User::class)->find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('Utilisateur non trouvé');
        }

        $company = $this->em->getRepository(Company::class)->find($companyId);
        if (!$company) {
            throw new \InvalidArgumentException('Entreprise non trouvée');
        }

        if ($user->getCompany() && $user->getCompany()->getId() === $companyId) {
            throw new \InvalidArgumentException('L\'utilisateur est déjà dans cette entreprise');
        }

        $user->setCompany($company);

        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }
        $user->setRoles($roles);

        foreach ($user->getUserPermissions() as $userPermission) {
            $this->em->remove($userPermission);
        }

        foreach ($permissions as $permission) {
            try {
                $userPermission = new \App\Entity\UserPermission();
                $userPermission->setUser($user);
                
                $permissionEnum = \App\Enum\Permission::from($permission);
                $userPermission->setPermission($permissionEnum);
                $this->em->persist($userPermission);
            } catch (\ValueError $e) {
                continue;
            }
        }

        $this->em->flush();

        return [
            'success' => true,
            'message' => 'Utilisateur assigné avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'companyId' => $company->getId(),
                'companyName' => $company->getName()
            ]
        ];
    }

    public function getAvailableUsersForCompany(int $companyId): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
           ->from(\App\Entity\User::class, 'u')
           ->where('u.company IS NULL OR u.company != :companyId')
           ->andWhere('u.deletedAt IS NULL')
           ->andWhere('u.isActive = :isActive')
           ->andWhere('u.isVerified = :isVerified')
           ->setParameter('companyId', $companyId)
           ->setParameter('isActive', true)
           ->setParameter('isVerified', true)
           ->orderBy('u.firstName', 'ASC')
           ->addOrderBy('u.lastName', 'ASC');

        $users = $qb->getQuery()->getResult();

        $availableUsers = [];
        foreach ($users as $user) {
            $availableUsers[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'pseudo' => $user->getPseudo(),
                'currentCompany' => $user->getCompany() ? [
                    'id' => $user->getCompany()->getId(),
                    'name' => $user->getCompany()->getName()
                ] : null,
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified()
            ];
        }

        return $availableUsers;
    }
}
