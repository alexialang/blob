<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findActiveUsersForLeaderboard(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.deletedAt IS NULL')
            ->andWhere('u.isActive = true')
            ->getQuery()
            ->getResult();
    }

    public function findUserGameHistory(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.email, u.firstName, u.lastName, u.pseudo')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult();
    }

    public function findUsersFromOtherCompanies(int $excludeCompanyId): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.company', 'c')
            ->addSelect('c')
            ->where('u.company IS NOT NULL')
            ->andWhere('u.company != :excludeCompanyId')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('u.isActive = true')
            ->setParameter('excludeCompanyId', $excludeCompanyId)
            ->getQuery()
            ->getResult();
    }

    public function findAllWithStats(bool $includeDeleted = false): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.company', 'c')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.userPermissions', 'up')
            ->addSelect('c', 'g', 'up');

        if (!$includeDeleted) {
            $qb->andWhere('u.deletedAt IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    public function findByCompanyWithStats(int $companyId, bool $includeDeleted = false): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.company', 'c')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.userPermissions', 'up')
            ->addSelect('c', 'g', 'up')
            ->where('u.company = :companyId')
            ->setParameter('companyId', $companyId);

        if (!$includeDeleted) {
            $qb->andWhere('u.deletedAt IS NULL');
        }

        return $qb->getQuery()->getResult();
    }


    public function findWithStats(int $userId): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.company', 'c')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.userPermissions', 'up')
            ->addSelect('c', 'g', 'up')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
