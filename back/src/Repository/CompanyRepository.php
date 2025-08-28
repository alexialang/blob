<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Group;
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

    public function findByUserAdmin(User $user): array
    {
        if ($user->getCompany()) {
            return [$user->getCompany()];
        }
        
        return [];
    }



    public function findGroupInCompany(int $groupId, int $companyId): ?Group
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('g')
           ->from(Group::class, 'g')
           ->where('g.id = :groupId')
           ->andWhere('g.company = :companyId')
           ->setParameter('groupId', $groupId)
           ->setParameter('companyId', $companyId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.users', 'u')
            ->leftJoin('c.groups', 'g')
            ->leftJoin('g.users', 'gu')
            ->addSelect('u', 'g', 'gu')
            ->getQuery()
            ->getResult();
    }

    public function findGroupsWithUsersByCompany(int $companyId): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('g', 'u')
           ->from(Group::class, 'g')
           ->leftJoin('g.users', 'u')
           ->where('g.company = :companyId')
           ->setParameter('companyId', $companyId)
           ->orderBy('g.name', 'ASC')
           ->addOrderBy('u.firstName', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function isUserInGroup(int $groupId, int $userId): bool
    {
        $result = $this->createQueryBuilder('c')
            ->select('COUNT(u.id)')
            ->from(Group::class, 'g')
            ->leftJoin('g.users', 'u')
            ->where('g.id = :groupId')
            ->andWhere('u.id = :userId')
            ->setParameter('groupId', $groupId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
