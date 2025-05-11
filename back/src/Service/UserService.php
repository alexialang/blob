<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\CompanyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

    public function list(): array
    {
        return $this->userRepository->findAll();
    }

    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function create(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $user->setDateRegistration(new \DateTimeImmutable());
        $user->setLastAcces(new \DateTime());
        $user->setRoles(['ROLE_USER']);
        $user->setIsAdmin($data['is_admin'] ?? false);

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
        if (isset($data['name'])) {
            $user->setName($data['name']);
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
        if (isset($data['last_acces'])) {
            $user->setLastAcces(new \DateTime($data['last_acces']));
        }

        $this->em->flush();

        return $user;
    }

    public function delete(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }
}
