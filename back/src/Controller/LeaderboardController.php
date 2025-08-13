<?php

namespace App\Controller;

use App\Entity\UserAnswer;
use App\Entity\Quiz;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class LeaderboardController extends AbstractController
{
    #[Route('/leaderboard/quiz/{id}', name: 'quiz_leaderboard', methods: ['GET'])]
    public function getQuizLeaderboard(int $id, EntityManagerInterface $em): JsonResponse
    {
        $quiz = $em->getRepository(Quiz::class)->find($id);
        if (!$quiz) {
            return new JsonResponse(['error' => 'Quiz non trouvé'], 404);
        }

        $qb = $em->createQueryBuilder();
        $results = $qb->select('ua.total_score as score, u.pseudo as name, c.name as company, u.id as userId')
            ->from(UserAnswer::class, 'ua')
            ->join('ua.user', 'u')
            ->leftJoin('u.company', 'c')
            ->where('ua.quiz = :quiz')
            ->setParameter('quiz', $quiz)
            ->orderBy('ua.total_score', 'DESC')
            ->addOrderBy('ua.date_attempt', 'ASC')
            ->getQuery()
            ->getResult();

        $leaderboard = [];
        $currentUserRank = null;
        $currentUser = $this->getUser();
        $currentUserId = $currentUser instanceof User ? $currentUser->getId() : null;
        
        foreach ($results as $index => $result) {
            $rank = $index + 1;
            $isCurrentUser = $currentUserId && $result['userId'] == $currentUserId;
            
            if ($isCurrentUser) {
                $currentUserRank = $rank;
            }

            $leaderboard[] = [
                'rank' => $rank,
                'name' => $result['name'] ?: 'Joueur anonyme',
                'company' => $result['company'] ?: 'Aucune entreprise',
                'score' => $result['score'],
                'isCurrentUser' => $isCurrentUser
            ];
        }

        return new JsonResponse([
            'leaderboard' => array_slice($leaderboard, 0, 10),
            'currentUserRank' => $currentUserRank ?: count($results) + 1,
            'totalPlayers' => count($results),
            'currentUserScore' => $currentUserId ? $this->getCurrentUserScore($id, $currentUserId, $em) : 0
        ]);
    }

    #[Route('/leaderboard', name: 'general_leaderboard', methods: ['GET'])]
    public function getGeneralLeaderboard(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $limit = $request->query->get('limit', 50);
        
        $qb = $em->createQueryBuilder();
        $results = $qb->select('
                SUM(ua.total_score) as totalScore,
                COUNT(ua.id) as quizzesCompleted,
                AVG(ua.total_score) as averageScore,
                u.id as userId,
                u.pseudo,
                u.firstName,
                u.lastName,
                u.avatar,
                c.name as companyName
            ')
            ->from(UserAnswer::class, 'ua')
            ->join('ua.user', 'u')
            ->leftJoin('u.company', 'c')
            ->where('u.isVerified = true AND u.isActive = true')
            ->groupBy('u.id')
            ->orderBy('totalScore', 'DESC')
            ->addOrderBy('averageScore', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $leaderboard = [];
        $currentUser = $this->getUser();
        $currentUserId = $currentUser instanceof User ? $currentUser->getId() : null;
        $currentUserPosition = null;
        
        foreach ($results as $index => $result) {
            $position = $index + 1;
            $isCurrentUser = $currentUserId && $result['userId'] == $currentUserId;
            
            if ($isCurrentUser) {
                $currentUserPosition = $position;
            }

            $leaderboard[] = [
                'id' => $result['userId'],
                'position' => $position,
                'pseudo' => $result['pseudo'] ?: ($result['firstName'] . ' ' . $result['lastName']),
                'firstName' => $result['firstName'],
                'lastName' => $result['lastName'],
                'avatar' => $result['avatar'] ?: 'default',
                'totalScore' => (int)$result['totalScore'],
                'averageScore' => round((float)$result['averageScore'], 1),
                'quizzesCompleted' => (int)$result['quizzesCompleted'],
                'badgesCount' => 0,
                'rankingScore' => (int)$result['totalScore'],
                'memberSince' => null,  
                'isCurrentUser' => $isCurrentUser
            ];
        }

        if ($currentUserId && !$currentUserPosition) {
            $userTotalScore = $this->getCurrentUserTotalScore($currentUserId, $em);
            $usersWithBetterScores = $em->createQueryBuilder()
                ->select('COUNT(DISTINCT u.id)')
                ->from(UserAnswer::class, 'ua')
                ->join('ua.user', 'u')
                ->where('u.isVerified = true AND u.isActive = true')
                ->groupBy('u.id')
                ->having('SUM(ua.total_score) > :userScore')
                ->setParameter('userScore', $userTotalScore)
                ->getQuery()
                ->getSingleScalarResult();
            
            $currentUserPosition = $usersWithBetterScores + 1;
        }

        $totalUsers = $em->createQueryBuilder()
            ->select('COUNT(DISTINCT u.id)')
            ->from(UserAnswer::class, 'ua')
            ->join('ua.user', 'u')
            ->where('u.isVerified = true AND u.isActive = true')
            ->getQuery()
            ->getSingleScalarResult();

        return new JsonResponse([
            'leaderboard' => $leaderboard,
            'currentUser' => [
                'position' => $currentUserPosition ?: $totalUsers + 1,
                'data' => $currentUser ? $this->getCurrentUserLeaderboardData($currentUser, $em) : null,
                'totalUsers' => (int)$totalUsers
            ],
            'meta' => [
                'totalUsers' => (int)$totalUsers,
                'limit' => (int)$limit,
                'generatedAt' => (new \DateTime())->format('Y-m-d H:i:s')
            ]
        ]);
    }

    private function getCurrentUserScore(int $quizId, int $userId, EntityManagerInterface $em): int
    {
        $result = $em->createQueryBuilder()
            ->select('MAX(ua.total_score) as maxScore')
            ->from(UserAnswer::class, 'ua')
            ->where('ua.quiz = :quiz AND ua.user = :user')
            ->setParameter('quiz', $quizId)
            ->setParameter('user', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?: 0;
    }

    private function getCurrentUserTotalScore(int $userId, EntityManagerInterface $em): int
    {
        $result = $em->createQueryBuilder()
            ->select('SUM(ua.total_score) as totalScore')
            ->from(UserAnswer::class, 'ua')
            ->where('ua.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?: 0;
    }

    private function getCurrentUserLeaderboardData(User $user, EntityManagerInterface $em): ?array
    {
        $stats = $em->createQueryBuilder()
            ->select('
                SUM(ua.total_score) as totalScore,
                COUNT(ua.id) as quizzesCompleted,
                AVG(ua.total_score) as averageScore
            ')
            ->from(UserAnswer::class, 'ua')
            ->where('ua.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();

        if (!$stats || !$stats['totalScore']) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'pseudo' => $user->getPseudo() ?: ($user->getFirstName() . ' ' . $user->getLastName()),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'avatar' => $user->getAvatar() ?: 'default',
            'totalScore' => (int)$stats['totalScore'],
            'averageScore' => round((float)$stats['averageScore'], 1),
            'quizzesCompleted' => (int)$stats['quizzesCompleted'],
            'badgesCount' => $user->getBadges()->count(),
            'rankingScore' => (int)$stats['totalScore'],
            'memberSince' => $user->getDateRegistration()?->format('Y-m-d'),
            'isCurrentUser' => true
        ];
    }
}
