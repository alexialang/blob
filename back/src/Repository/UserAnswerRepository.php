<?php

namespace App\Repository;

use App\Entity\Quiz;
use App\Entity\UserAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAnswer::class);
    }

    public function findQuizLeaderboard(int $quizId): array
    {
        $qb = $this->createQueryBuilder('ua');
        $results = $qb->select('MAX(ua.total_score) as score, u.pseudo as name, u.firstName as firstName, u.lastName as lastName, c.name as company, u.id as userId, MIN(ua.date_attempt) as firstAttempt')
            ->join('ua.user', 'u')
            ->leftJoin('u.company', 'c')
            ->where('ua.quiz = :quiz')
            ->setParameter('quiz', $quizId)
            ->groupBy('u.id')
            ->orderBy('score', 'DESC')
            ->addOrderBy('firstAttempt', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function findMaxScoreForUserAndQuiz(int $quizId, int $userId): int
    {
        $result = $this->createQueryBuilder('ua')
            ->select('MAX(ua.total_score) as maxScore')
            ->where('ua.quiz = :quiz AND ua.user = :user')
            ->setParameter('quiz', $quizId)
            ->setParameter('user', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?: 0;
    }

    public function getQuizLeaderboardData(Quiz $quiz): array
    {
        $qb = $this->createQueryBuilder('ua');

        return $qb->select('ua.total_score as score, u.pseudo as name, c.name as company, u.id as userId')
            ->join('ua.user', 'u')
            ->leftJoin('u.company', 'c')
            ->where('ua.quiz = :quiz')
            ->setParameter('quiz', $quiz)
            ->orderBy('ua.total_score', 'DESC')
            ->addOrderBy('ua.date_attempt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getGeneralLeaderboardData(int $limit): array
    {
        $users = $this->createQueryBuilder('ua')
            ->select('DISTINCT u.id as userId')
            ->join('ua.user', 'u')
            ->leftJoin('u.company', 'c')
            ->where('u.isVerified = true AND u.isActive = true')
            ->getQuery()
            ->getResult();

        $leaderboardData = [];

        foreach ($users as $user) {
            $userStats = $this->getUserStats($user['userId']);
            if ($userStats) {
                $userData = $this->createQueryBuilder('ua')
                    ->select('u.pseudo, u.firstName, u.lastName, u.avatar, c.name as companyName')
                    ->join('ua.user', 'u')
                    ->leftJoin('u.company', 'c')
                    ->where('u.id = :userId')
                    ->setParameter('userId', $user['userId'])
                    ->getQuery()
                    ->getSingleResult();

                $leaderboardData[] = [
                    'totalScore' => $userStats['totalScore'],
                    'quizzesCompleted' => $userStats['quizzesCompleted'],
                    'averageScore' => $userStats['averageScore'],
                    'userId' => $user['userId'],
                    'pseudo' => $userData['pseudo'],
                    'firstName' => $userData['firstName'],
                    'lastName' => $userData['lastName'],
                    'avatar' => $userData['avatar'],
                    'companyName' => $userData['companyName'],
                ];
            }
        }

        usort($leaderboardData, function ($a, $b) {
            return $b['totalScore'] - $a['totalScore'];
        });

        return array_slice($leaderboardData, 0, $limit);
    }

    public function getUserMaxScoreForQuiz(int $quizId, int $userId): int
    {
        $result = $this->createQueryBuilder('ua')
            ->select('MAX(ua.total_score) as maxScore')
            ->where('ua.quiz = :quiz AND ua.user = :user')
            ->setParameter('quiz', $quizId)
            ->setParameter('user', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?: 0;
    }

    public function getUserTotalScore(int $userId): int
    {
        $result = $this->createQueryBuilder('ua')
            ->select('ua.total_score, ua.quiz')
            ->where('ua.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getResult();

        $quizScores = [];
        foreach ($result as $row) {
            $quizId = $row['quiz']->getId();
            if (!isset($quizScores[$quizId])) {
                $quizScores[$quizId] = $row['total_score'];
            }
        }

        return array_sum($quizScores);
    }

    public function getUsersWithBetterScoreCount(int $userScore): int
    {
        return $this->createQueryBuilder('ua')
            ->select('COUNT(DISTINCT u.id)')
            ->join('ua.user', 'u')
            ->where('u.isVerified = true AND u.isActive = true')
            ->groupBy('u.id')
            ->having('SUM(ua.total_score) > :userScore')
            ->setParameter('userScore', $userScore)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalActiveUsersCount(): int
    {
        return $this->createQueryBuilder('ua')
            ->select('COUNT(DISTINCT u.id)')
            ->join('ua.user', 'u')
            ->where('u.isVerified = true AND u.isActive = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getUserStats(int $userId): ?array
    {
        $result = $this->createQueryBuilder('ua')
            ->select('ua.total_score, ua.quiz')
            ->where('ua.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getResult();

        if (empty($result)) {
            return null;
        }

        $quizScores = [];
        foreach ($result as $row) {
            $quizId = $row['quiz']->getId();
            if (!isset($quizScores[$quizId])) {
                $quizScores[$quizId] = $row['total_score'];
            }
        }

        $totalScore = array_sum($quizScores);
        $quizzesCompleted = count($quizScores);
        $averageScore = $quizzesCompleted > 0 ? round($totalScore / $quizzesCompleted, 1) : 0;

        return [
            'totalScore' => $totalScore,
            'quizzesCompleted' => $quizzesCompleted,
            'averageScore' => $averageScore,
        ];
    }

    public function getUserAnswersWithQuizData(int $userId): array
    {
        return $this->createQueryBuilder('ua')
            ->select('ua.total_score, ua.date_attempt, 
                     q.id as quiz_id, q.title as quiz_title, 
                     c.name as category_name')
            ->leftJoin('ua.quiz', 'q')
            ->leftJoin('q.category', 'c')
            ->where('ua.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ua.date_attempt', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }
}
