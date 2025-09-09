<?php

namespace App\Service;

use App\Entity\GameSession;
use App\Entity\User;

class MultiplayerScoreService
{
    private static array $gameAnswers = [];

    /**
     * Normalise un score brut en pourcentage.
     */
    public function normalizeScoreToPercentage(int $score, int $totalQuestions): int
    {
        return $totalQuestions > 0 ? round(($score / ($totalQuestions * 10)) * 100) : 0;
    }

    /**
     * Calcule les points gagnés pour une réponse.
     */
    public function calculatePoints(bool $isCorrect, int $timeSpent): int
    {
        return $isCorrect ? max(10 - floor($timeSpent / 3), 1) : 0;
    }

    /**
     * Enregistre une réponse dans le cache temporaire.
     */
    public function recordAnswer(string $gameCode, User $user, int $questionId, bool $isCorrect, int $points): void
    {
        $answerKey = 'game_'.$gameCode.'_user_'.$user->getId().'_q_'.$questionId;
        self::$gameAnswers[$answerKey] = [
            'userId' => $user->getId(),
            'questionId' => $questionId,
            'isCorrect' => $isCorrect,
            'points' => $points,
            'timestamp' => time(),
        ];
    }

    /**
     * Calcule le score total d'un joueur pour une partie.
     */
    public function calculateTotalScore(string $gameCode, User $user): int
    {
        $totalScore = 0;
        foreach (self::$gameAnswers as $key => $answerData) {
            if (str_starts_with((string) $key, 'game_'.$gameCode.'_user_'.$user->getId())) {
                $totalScore += $answerData['points'];
            }
        }

        return $totalScore;
    }

    /**
     * Met à jour le leaderboard avec les scores actuels.
     */
    public function updateLeaderboard(GameSession $gameSession): array
    {
        $gameCode = $gameSession->getGameCode();
        $leaderboard = [];
        $sharedScores = $gameSession->getSharedScores() ?? [];

        foreach ($gameSession->getRoom()->getPlayers() as $roomPlayer) {
            $user = $roomPlayer->getUser();
            $userId = $user->getId();
            $username = $user->getPseudo() ?: ($user->getFirstName().' '.$user->getLastName()) ?: ('Joueur '.$user->getId());

            $totalScore = $sharedScores[$username] ?? 0;

            $leaderboard[] = [
                'userId' => $userId,
                'username' => $username,
                'score' => $totalScore,
                'team' => $roomPlayer->getTeam(),
            ];
        }

        usort($leaderboard, fn ($a, $b) => $b['score'] - $a['score']);

        foreach ($leaderboard as $index => &$entry) {
            $entry['position'] = $index + 1;
        }

        return $leaderboard;
    }

    /**
     * Nettoie le cache des réponses pour une partie terminée.
     */
    public function clearGameAnswers(string $gameCode): void
    {
        foreach (self::$gameAnswers as $key => $answer) {
            if (str_starts_with((string) $key, 'game_'.$gameCode)) {
                unset(self::$gameAnswers[$key]);
            }
        }
    }
}
