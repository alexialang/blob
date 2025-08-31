<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\User;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use App\Entity\UserPermission;
use App\Enum\Permission;

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
        return $this->companyRepository->findAllWithRelations();
    }

    public function findByUser(User $user): array
    {
        if ($user->getCompany()) {
            return [$user->getCompany()];
        }
        return [];
    }

    public function find(int $id): ?Company
    {
        return $this->companyRepository->find($id);
    }

    public function create(array $data): Company
    {

        $this->em->beginTransaction();
        
        try {
            $company = new Company();
            $company->setName($data['name']);
            $company->setDateCreation(new \DateTime());

            $this->em->persist($company);
            $this->em->flush();
            
            $this->em->commit();
            return $company;
            
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function update(Company $company, array $data): Company
    {

        $this->em->beginTransaction();
        
        try {
            if (isset($data['name'])) {
                $company->setName($data['name']);
            }

            $this->em->flush();
            $this->em->commit();
            return $company;
            
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function delete(Company $company): void
    {
        $this->em->beginTransaction();
        
        try {
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
            
            $this->em->commit();
            
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
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
        $companies = $this->companyRepository->findAllWithRelations();
        
        $data = json_decode(
            $this->serializer->serialize($companies, 'json', ['groups' => ['company:detail']]),
            true
        );
        
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function importCompaniesFromCsv(UploadedFile $file): array
    {
        $content = file_get_contents($file->getPathname());
        $lines = explode("\n", trim($content));
        
        $results = ['success' => 0, 'errors' => []];
        
        array_shift($lines);

        $this->em->beginTransaction();
        
        try {
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
            
            $this->em->commit();
            return $results;
            
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function getCompanyStats(Company $company): array
    {
        $activeUsersCount = 0;
        $lastActivity = null;
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        foreach ($company->getUsers() as $user) {
            if ($user->getLastAccess() && $user->getLastAccess() > $thirtyDaysAgo && $user->isActive()) {
                $activeUsersCount++;
            }
            
            if ($user->getLastAccess() && (!$lastActivity || $user->getLastAccess() > $lastActivity)) {
                $lastActivity = $user->getLastAccess();
            }
        }

        return [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'userCount' => $company->getUsers()->count(),
            'activeUsers' => $activeUsersCount,
            'groupCount' => $company->getGroups()->count(),
            'quizCount' => $company->getQuizs()->count(),
            'createdAt' => $company->getDateCreation() ? $company->getDateCreation()->format('Y-m-d H:i:s') : null,
            'lastActivity' => $lastActivity ? $lastActivity->format('Y-m-d H:i:s') : null
        ];
    }

    private function getLastActivity(Company $company): ?string
    {
        $lastActivity = null;
        
        foreach ($company->getUsers() as $user) {
            if ($user->getLastAccess()) {
                if (!$lastActivity || $user->getLastAccess() > $lastActivity) {
                    $lastActivity = $user->getLastAccess();
                }
            }
        }
        
        return $lastActivity ? $lastActivity->format('Y-m-d H:i:s') : null;
    }

    public function assignUserToCompany(Company $company, int $userId, array $roles, array $permissions): array
    {
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('Utilisateur non trouvé');
        }
        
        if ($user->getCompany() && $user->getCompany()->getId() === $company->getId()) {
            throw new \InvalidArgumentException('L\'utilisateur est déjà dans cette entreprise');
        }
        
        $user->setCompany($company);
        
        $user->setRoles($roles);
        
        $existingPermissions = $this->em->getRepository(UserPermission::class)->findBy(['user' => $user]);
        foreach ($existingPermissions as $permission) {
            $this->em->remove($permission);
        }
        
        foreach ($permissions as $permissionName) {
            try {
                $permission = Permission::from($permissionName);
                $userPermission = new UserPermission();
                $userPermission->setUser($user);
                $userPermission->setPermission($permission);
                $this->em->persist($userPermission);
            } catch (\ValueError $e) {
                continue;
            }
        }
        
        $this->em->flush();
        
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $roles,
            'companyId' => $company->getId(),
            'companyName' => $company->getName()
        ];
    }

    private function validateCompanyData(array $data): void
    {
        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(['message' => 'Le nom de l\'entreprise est requis']),
                new Assert\Length(['max' => 255, 'maxMessage' => 'Le nom ne peut pas dépasser 255 caractères'])
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }

    public function getCompanyGroups(Company $company): array
    {
        $groups = $this->companyRepository->findGroupsWithUsersByCompany($company->getId());
        
        $data = [];
        foreach ($groups as $group) {
            $groupUsers = [];
            foreach ($group->getUsers() as $user) {
                $groupUsers[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'pseudo' => $user->getPseudo(),
                    'avatar' => $user->getAvatar()
                ];
            }
            
            $data[] = [
                'id' => $group->getId(),
                'name' => $group->getName(),
                'accesCode' => $group->getAccesCode(),
                'userCount' => $group->getUsers()->count(),
                'users' => $groupUsers
            ];
        }
        
        return $data;
    }
}
