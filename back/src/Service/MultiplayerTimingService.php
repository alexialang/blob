<?php

namespace App\Service;

use App\Entity\GameSession;
use Doctrine\ORM\EntityManagerInterface;

class MultiplayerTimingService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Configure le timing d'une nouvelle question
     */
    public function setupQuestionTiming(GameSession $gameSession, int $duration = 30): void
    {
        $gameSession->setCurrentQuestionStartedAt(new \DateTimeImmutable());
        $gameSession->setCurrentQuestionDuration($duration);
        $this->entityManager->flush();
    }

    /**
     * Calcule le temps restant pour une question
     */
    public function calculateTimeLeft(GameSession $gameSession): int
    {
        if (!$gameSession->getCurrentQuestionStartedAt() || !$gameSession->getCurrentQuestionDuration()) {
            return 30; // valeur par défaut
        }

        $elapsedTime = time() - $gameSession->getCurrentQuestionStartedAt()->getTimestamp();
        return max(0, $gameSession->getCurrentQuestionDuration() - $elapsedTime);
    }

    /**
     * Vérifie si le temps est écoulé pour une question
     */
    public function isTimeExpired(GameSession $gameSession): bool
    {
        return $this->calculateTimeLeft($gameSession) <= 0;
    }

    /**
     * Met à jour automatiquement le timing si manquant
     */
    public function ensureTimingExists(GameSession $gameSession): void
    {
        if (!$gameSession->getCurrentQuestionStartedAt() || !$gameSession->getCurrentQuestionDuration()) {
            $this->setupQuestionTiming($gameSession);

        }
    }

    /**
     * Vérifie la protection anti-spam pour les transitions
     */
    public function checkTransitionCooldown(GameSession $gameSession, int $cooldownSeconds = 3): bool
    {
        $lastQuestionStartTime = $gameSession->getCurrentQuestionStartedAt();
        if ($lastQuestionStartTime && (time() - $lastQuestionStartTime->getTimestamp()) < $cooldownSeconds) {

            return false;
        }
        return true;
    }
}
