<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\CompanyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserService
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private CompanyService $companyService;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository, CompanyService $companyService, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->companyService = $companyService;
        $this->passwordHasher = $passwordHasher;
    }

    public function list(bool $includeDeleted = false): array
    {
        if ($includeDeleted) {
            return $this->userRepository->findAll();
        } else {
            return $this->userRepository->findBy(['deletedAt' => null]);
        }
    }

    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function create(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');
        $user->setDateRegistration(new \DateTimeImmutable());
        $user->setLastAccess(new \DateTime());
        $user->setRoles(['ROLE_USER']);
        $user->setIsAdmin($data['is_admin'] ?? false);
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        if (isset($data['company_id'])) {
            $company = $this->companyService->find($data['company_id']);
            $user->setCompany($company);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }
        if (isset($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }
        if (isset($data['company_id'])) {
            $company = $this->companyService->find($data['company_id']);
            $user->setCompany($company);
        }
        if (isset($data['is_admin'])) {
            $user->setIsAdmin($data['is_admin']);
        }
        if (isset($data['lastAccess'])) {
            $user->setLastAccess(new \DateTime($data['lastAccess']));
        }
        if (isset($data['isActive'])) {
            $user->setIsActive($data['isActive']);
        }

        $this->em->flush();

        return $user;
    }


    public function delete(User $user): void
    {

        $user->setDeletedAt(new \DateTimeImmutable());
        $user->setIsActive(false);

        $user->setEmail('deleted_user_'.$user->getId().'@example.com');
        $user->setFirstName('Deleted');
        $user->setLastName('User');
        $user->setPassword('');
        $user->setRoles([]);
        $user->setIsAdmin(false);
        $user->setCompany(null);

        $this->em->flush();
    }
}
