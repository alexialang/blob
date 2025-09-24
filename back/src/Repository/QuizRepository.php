<?php

namespace App\Repository;

use App\Entity\Quiz;
use App\Entity\User;
use App\Enum\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
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

    /**
     * @param bool $forManagement Si true, retourne tous les quiz, sinon seulement les publics
     *
     * @return array Liste des quiz filtrés
     */
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

    /**
     * Trouve les quiz avec pagination pour la gestion.
     *
     * @param int         $page   Numéro de page
     * @param int         $limit  Nombre d'éléments par page
     * @param string|null $search Terme de recherche
     * @param string      $sort   Champ de tri
     * @param User        $user   Utilisateur demandant les quiz
     *
     * @return array Résultat avec données et pagination
     */
    public function findWithPagination(int $page, int $limit, ?string $search, string $sort, User $user): array
    {
        $queryBuilder = $this->createQueryBuilder('q')
            ->leftJoin('q.user', 'u')
            ->leftJoin('q.category', 'c')
            ->addSelect('u', 'c');

        if (!$user->isAdmin()) {
            $company = $user->getCompany();
            if ($company) {
                $queryBuilder->andWhere('u.company = :company')
                    ->setParameter('company', $company);
            } else {
                $queryBuilder->andWhere('u.id = :userId')
                    ->setParameter('userId', $user->getId());
            }
        }

        if ($search && '' !== trim($search)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('q.title', ':search'),
                    $queryBuilder->expr()->like('q.description', ':search'),
                    $queryBuilder->expr()->like('u.email', ':search'),
                    $queryBuilder->expr()->like('u.firstName', ':search'),
                    $queryBuilder->expr()->like('u.lastName', ':search'),
                    $queryBuilder->expr()->like('c.name', ':search')
                )
            )->setParameter('search', '%'.trim($search).'%');
        }

        // Tri
        $allowedSorts = ['id', 'title', 'dateCreation', 'status'];
        if (in_array($sort, $allowedSorts, true)) {
            $queryBuilder->orderBy('q.'.$sort, 'ASC');
        } else {
            $queryBuilder->orderBy('q.id', 'ASC');
        }

        $countQuery = clone $queryBuilder;
        $total = (int) $countQuery->select('COUNT(q.id)')->getQuery()->getSingleScalarResult();

        $offset = ($page - 1) * $limit;
        $queryBuilder->setFirstResult($offset)->setMaxResults($limit);

        $data = $queryBuilder->getQuery()->getResult();

        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ];
    }

    /**
     * @param array $userGroupIds IDs des groupes de l'utilisateur
     *
     * @return array Quiz privés accessibles
     */
    public function findPrivateQuizzesForUserGroups(array $userGroupIds): array
    {
        if (empty($userGroupIds)) {
            return [];
        }

        $query = $this->createQueryBuilder('q')
            ->join('q.groups', 'g')
            ->where('q.isPublic = false')
            ->andWhere('q.status = :status')
            ->andWhere('g.id IN (:groupIds)')
            ->setParameter('status', Status::PUBLISHED->value)
            ->setParameter('groupIds', $userGroupIds)
            ->distinct()
            ->orderBy('q.date_creation', 'DESC')
            ->getQuery();

        $result = $query->getResult();

        return $result;
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

    public function canUserModifyQuiz(int $quizId, User $user): bool
    {
        $result = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.id = :quizId')
            ->andWhere('(q.user = :userId OR q.company = :companyId)')
            ->setParameter('quizId', $quizId)
            ->setParameter('userId', $user->getId())
            ->setParameter('companyId', $user->getCompany() ? $user->getCompany()->getId() : 0)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    public function findWithUserAccess(int $quizId, User $user): ?Quiz
    {
        $qb = $this->createQueryBuilder('q')
            ->leftJoin('q.user', 'u')
            ->leftJoin('q.company', 'c')
            ->leftJoin('q.category', 'cat')
            ->leftJoin('q.questions', 'quest')
            ->leftJoin('quest.answers', 'ans')
            ->addSelect('u', 'c', 'cat', 'quest', 'ans')
            ->where('q.id = :quizId')
            ->setParameter('quizId', $quizId);

        $accessConditions = [];
        $accessConditions[] = 'q.user = :userId';
        $qb->setParameter('userId', $user->getId());

        if ($user->getCompany()) {
            $accessConditions[] = 'q.company = :companyId';
            $qb->setParameter('companyId', $user->getCompany()->getId());
        }

        $accessConditions[] = 'q.isPublic = :isPublic';
        $qb->setParameter('isPublic', true);

        $qb->andWhere('('.implode(' OR ', $accessConditions).')');

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findWithAllRelations(int $quizId): ?Quiz
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.user', 'u')
            ->leftJoin('q.company', 'c')
            ->leftJoin('q.category', 'cat')
            ->leftJoin('q.groups', 'g')
            ->leftJoin('q.questions', 'quest')
            ->leftJoin('quest.answers', 'ans')
            ->leftJoin('quest.type_question', 'qt')
            ->addSelect('u', 'c', 'cat', 'g', 'quest', 'ans', 'qt')
            ->where('q.id = :quizId')
            ->setParameter('quizId', $quizId)
            ->getQuery()
            ->setFetchMode(\App\Entity\Question::class, 'answers', ClassMetadata::FETCH_EAGER)
            ->setFetchMode(\App\Entity\Question::class, 'type_question', ClassMetadata::FETCH_EAGER)
            ->getOneOrNullResult();
    }

    public function findPrivateQuizzesForUser(User $user): array
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.groups', 'g')
            ->leftJoin('g.users', 'u')
            ->where('q.isPublic = false')
            ->andWhere('q.status = :status')
            ->andWhere('u.id = :userId')  // Quiz accessibles via les groupes de l'utilisateur
            ->setParameter('status', Status::PUBLISHED->value)
            ->setParameter('userId', $user->getId())
            ->distinct()
            ->orderBy('q.date_creation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
