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
        
        error_log('findPrivateQuizzesForUserGroups - Groupes: ' . implode(',', $userGroupIds) . ' - Résultats: ' . count($result));
        
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



    public function getPublicLeaderboard(Quiz $quiz): array
    {
        $leaderboard = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u.firstName, u.lastName, u.pseudo, c.name as companyName, ua.total_score as score, ua.date_attempt')
            ->from('App\Entity\UserAnswer', 'ua')
            ->join('ua.user', 'u')
            ->leftJoin('u.company', 'c')
            ->where('ua.quiz = :quiz')
            ->setParameter('quiz', $quiz)
            ->orderBy('ua.total_score', 'DESC')
            ->addOrderBy('ua.date_attempt', 'ASC')
            ->getQuery()
            ->getResult();

        $userBestScores = [];
        foreach ($leaderboard as $entry) {
            $userId = $entry['firstName'] . ' ' . $entry['lastName'];
            if (!isset($userBestScores[$userId]) || $entry['score'] > $userBestScores[$userId]['score']) {
                $userBestScores[$userId] = $entry;
            }
        }

        uasort($userBestScores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $formattedLeaderboard = [];
        $rank = 1;
        foreach ($userBestScores as $entry) {
            $displayName = $entry['pseudo'] ?? ($entry['firstName'] . ' ' . substr($entry['lastName'], 0, 1) . '.');
            $formattedLeaderboard[] = [
                'rank' => $rank,
                'name' => $displayName,
                'company' => $entry['companyName'] ?? 'Indépendant',
                'score' => (int)$entry['score'],
                'isCurrentUser' => false
            ];
            $rank++;
        }

        return [
            'leaderboard' => $formattedLeaderboard,
            'totalPlayers' => count($userBestScores)
        ];
    }
}
