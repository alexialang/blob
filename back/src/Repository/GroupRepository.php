<?php

namespace App\Repository;

use App\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function findByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.users', 'u')
            ->addSelect('u')
            ->where('g.company = :companyId')
            ->setParameter('companyId', $companyId)
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function isUserInGroup(int $groupId, int $userId): bool
    {
        $result = $this->createQueryBuilder('g')
            ->select('COUNT(u.id)')
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
