<?php

namespace App\Service;

use App\Entity\Quiz;
use App\Entity\User;
use App\Repository\UserAnswerRepository;
use App\Repository\UserRepository;

class LeaderboardService
{
    public function __construct(
        private readonly UserAnswerRepository $userAnswerRepository,
        private readonly UserRepository $userRepository,
        private readonly UserService $userService,
    ) {
    }

    public function getQuizLeaderboard(Quiz $quiz, ?User $currentUser): array
    {
        $results = $this->userAnswerRepository->findQuizLeaderboard($quiz->getId());

        $leaderboard = [];
        $currentUserRank = null;
        $currentUserId = $currentUser?->getId();

        foreach ($results as $index => $result) {
            $rank = $index + 1;
            $isCurrentUser = $currentUserId && $result['userId'] == $currentUserId;

            if ($isCurrentUser) {
                $currentUserRank = $rank;
            }

            $displayName = $result['name'] ?: ($result['firstName'].' '.$result['lastName']);
            if (!$displayName || '' === trim((string) $displayName)) {
                $displayName = 'Joueur anonyme';
            }


            $leaderboard[] = [
                'rank' => $rank,
                'name' => trim((string) $displayName),
                'company' => $result['company'] ?: 'Aucune entreprise',
                'score' => $result['score'],
                'isCurrentUser' => $isCurrentUser,
            ];
        }





        // Si l'utilisateur actuel n'est pas dans les résultats, ne pas l'ajouter artificiellement
        // Juste retourner les résultats de la base de données
        return [
            'leaderboard' => array_slice($leaderboard, 0, 10),
            'currentUserRank' => $currentUserRank ?: (count($results) + 1),
            'totalPlayers' => count($results),
            'currentUserScore' => $currentUserId ? $this->getCurrentUserQuizScore($quiz->getId(), $currentUserId) : 0,
        ];
    }

    public function getGeneralLeaderboard(int $limit, ?User $currentUser): array
    {
        $users = $this->userRepository->findActiveUsersForLeaderboard();

        $leaderboard = [];
        $currentUserId = $currentUser?->getId();
        $currentUserPosition = null;

        $allUserStats = [];
        foreach ($users as $user) {
            $allUserStats[$user->getId()] = $this->userService->getUserStatistics($user);
        }

        foreach ($users as $user) {
            $position = count($leaderboard) + 1;
            $isCurrentUser = $currentUserId && $user->getId() == $currentUserId;

            if ($isCurrentUser) {
                $currentUserPosition = $position;
            }

            $userStats = $allUserStats[$user->getId()];

            $avatarShape = null;
            $avatarColor = null;

            if ($user->getAvatar()) {
                $avatarData = json_decode((string) $user->getAvatar(), true);
                $avatarShape = $avatarData['shape'] ?? null;
                $avatarColor = $avatarData['color'] ?? null;
            }

            $leaderboard[] = [
                'id' => $user->getId(),
                'rank' => $position,
                'position' => $position,
                'name' => $user->getPseudo() ?: ($user->getFirstName().' '.$user->getLastName()),
                'pseudo' => $user->getPseudo() ?: ($user->getFirstName().' '.$user->getLastName()),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'avatar' => $user->getAvatar() ?: 'default',
                'avatarShape' => $avatarShape,
                'avatarColor' => $avatarColor,
                'score' => $userStats['totalScore'],
                'totalScore' => $userStats['totalScore'],
                'averageScore' => $userStats['averageScore'],
                'quizzesCompleted' => $userStats['totalQuizzesCompleted'],
                'totalAttempts' => $userStats['totalAttempts'],
                'badgesCount' => $userStats['badgesEarned'],
                'rankingScore' => $userStats['totalScore'],
                'memberSince' => $userStats['memberSince'],
                'isCurrentUser' => $isCurrentUser,
            ];
        }

        usort($leaderboard, fn ($a, $b) => $b['totalScore'] - $a['totalScore']);

        foreach ($leaderboard as $index => &$userData) {
            $userData['position'] = $index + 1;
        }

        $currentUserPosition = null;
        if ($currentUserId) {
            foreach ($leaderboard as $index => $userData) {
                if ($userData['id'] === $currentUserId) {
                    $currentUserPosition = $index + 1;
                    break;
                }
            }
        }

        $limitedLeaderboard = array_slice($leaderboard, 0, $limit);
        $totalUsers = count($leaderboard);

        return [
            'leaderboard' => $limitedLeaderboard,
            'currentUser' => [
                'position' => $currentUserPosition ?: $totalUsers + 1,
                'data' => $currentUser ? $this->getCurrentUserLeaderboardData($currentUser, $allUserStats) : null,
                'totalUsers' => $totalUsers,
            ],
            'meta' => [
                'totalUsers' => $totalUsers,
                'limit' => (int) $limit,
                'generatedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            ],
        ];
    }

    private function getCurrentUserQuizScore(int $quizId, int $userId): int
    {
        return $this->userAnswerRepository->getUserMaxScoreForQuiz($quizId, $userId);
    }

    private function getCurrentUserLeaderboardData(User $user, array $allUserStats): ?array
    {
        $userStats = $allUserStats[$user->getId()] ?? null;

        if (!$userStats || !$userStats['totalScore']) {
            return null;
        }

        $avatarShape = null;
        $avatarColor = null;

        if ($user->getAvatar()) {
            $avatarData = json_decode($user->getAvatar(), true);
            $avatarShape = $avatarData['shape'] ?? null;
            $avatarColor = $avatarData['color'] ?? null;
        }

        return [
            'id' => $user->getId(),
            'pseudo' => $user->getPseudo() ?: ($user->getFirstName().' '.$user->getLastName()),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'avatar' => $user->getAvatar() ?: 'default',
            'avatarShape' => $avatarShape,
            'avatarColor' => $avatarColor,
            'totalScore' => $userStats['totalScore'],
            'averageScore' => $userStats['averageScore'],
            'quizzesCompleted' => $userStats['totalQuizzesCompleted'],
            'totalAttempts' => $userStats['totalAttempts'],
            'badgesCount' => $userStats['badgesEarned'],
            'rankingScore' => $userStats['totalScore'],
            'memberSince' => $userStats['memberSince'],
            'isCurrentUser' => true,
        ];
    }
}
