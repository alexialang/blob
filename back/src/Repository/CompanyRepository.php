<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * Trouve les entreprises où l'utilisateur est admin
     */
    public function findByUserAdmin(User $user): array
    {
        if ($user->getCompany()) {
            return [$user->getCompany()];
        }
        
        return [];
    }
}
