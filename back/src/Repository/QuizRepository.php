<?php

namespace App\Repository;

use App\Entity\Quiz;
use App\Entity\User;
use App\Enum\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    public function findPublishedOrAll(bool $forManagement = false): array
    {
        $queryBuilder = $this->createQueryBuilder('q');

        if (!$forManagement) {
            $queryBuilder->where('q.isPublic = true')
                ->andWhere('q.status = :publishedStatus')
                ->setParameter('publishedStatus', Status::PUBLISHED->value);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findPrivateQuizzesForUserGroups(array $userGroupIds): array
    {
        if (empty($userGroupIds)) {
            return [];
        }

        return $this->createQueryBuilder('q')
            ->join('q.groups', 'g')
            ->where('q.isPublic = false')
            ->andWhere('q.status = :status')
            ->andWhere('g.id IN (:groupIds)')
            ->setParameter('status', Status::PUBLISHED->value)
            ->setParameter('groupIds', $userGroupIds)
            ->distinct()
            ->orderBy('q.date_creation', 'DESC')
            ->getQuery()
            ->getResult();
    }



    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.user = :user')
            ->setParameter('user', $user)
            ->orderBy('q.date_creation', 'DESC')
            ->getQuery()
            ->getResult();
    }


    public function findMostPopular(int $limit = 8): array
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.userAnswers', 'ua')
            ->where('q.isPublic = true')
            ->andWhere('q.status = :status')
            ->setParameter('status', Status::PUBLISHED->value)
            ->groupBy('q.id')
            ->orderBy('COUNT(ua.id)', 'DESC')
            ->addOrderBy('q.date_creation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }


    public function findMostRecent(int $limit = 6): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.isPublic = true')
            ->andWhere('q.status = :status')
            ->setParameter('status', Status::PUBLISHED->value)
            ->orderBy('q.date_creation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
