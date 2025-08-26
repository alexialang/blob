import { Injectable } from '@angular/core';

/**
 * Service spécialisé pour la gestion du timing en mode multijoueur
 * Extrait de MultiplayerGameComponent pour une meilleure organisation
 */
@Injectable({
  providedIn: 'root'
})
export class MultiplayerTimingService {
  private timers: Map<string, any> = new Map();

  /**
   * Calcule l'offset avec le serveur
   */
  calculateServerTimeOffset(serverTime?: number): number {
    const currentServerTime = serverTime || Date.now();
    return currentServerTime - Date.now();
  }

  /**
   * Met à jour le timing d'une question depuis les données serveur
   */
  updateQuestionTiming(
    questionStartTime?: number,
    questionDuration?: number,
    serverTimeOffset: number = 0
  ): { timeLeft: number, questionStartTimestamp: number, questionDuration: number } {

    if (questionStartTime && questionDuration) {
      const questionStartTimestamp = questionStartTime * 1000;
      const currentTime = Date.now();
      const elapsedTime = Math.floor((currentTime - questionStartTimestamp) / 1000);
      const timeLeft = Math.max(0, questionDuration - elapsedTime);


      return {
        timeLeft,
        questionStartTimestamp,
        questionDuration
      };
    } else {
      return {
        timeLeft: 30,
        questionStartTimestamp: Date.now() + serverTimeOffset,
        questionDuration: 30
      };
    }
  }

  /**
   * Démarre un timer pour une question
   */
  startQuestionTimer(
    gameId: string,
    initialTimeLeft: number,
    onTick: (timeLeft: number) => void,
    onExpired: () => void
  ): void {
    this.stopTimer(gameId);

    let timeLeft = initialTimeLeft;
    const timer = setInterval(() => {
      if (timeLeft > 0) {
        timeLeft--;
        onTick(timeLeft);
        console.log(` Timer ${gameId}: ${timeLeft}s restant`);
      }

      if (timeLeft <= 0) {
        this.stopTimer(gameId);
        onExpired();
      }
    }, 1000);

    this.timers.set(gameId, timer);
    console.log(` Timer démarré pour ${gameId} avec ${initialTimeLeft}s`);
  }

  /**
   * Arrête un timer spécifique
   */
  stopTimer(gameId: string): void {
    const timer = this.timers.get(gameId);
    if (timer) {
      clearInterval(timer);
      this.timers.delete(gameId);
      console.log(` Timer arrêté pour ${gameId}`);
    }
  }

  /**
   * Arrête tous les timers
   */
  stopAllTimers(): void {
    this.timers.forEach((timer, gameId) => {
      clearInterval(timer);
      console.log(` Timer arrêté pour ${gameId}`);
    });
    this.timers.clear();
  }

  /**
   * Vérifie si un cooldown de transition est en cours
   */
  checkTransitionCooldown(lastProcessedTime: number, cooldownMs: number = 2000): boolean {
    const now = Date.now();
    const elapsed = now - lastProcessedTime;

    if (elapsed < cooldownMs) {
      console.log(` Transition bloquée - Cooldown actif (${elapsed}ms/${cooldownMs}ms)`);
      return false;
    }

    return true;
  }

  /**
   * Calcule le temps écoulé depuis le début d'une question
   */
  calculateElapsedTime(questionStartTime: number): number {
    return Math.floor((Date.now() - questionStartTime) / 1000);
  }

  /**
   * Nettoie tous les timers (à appeler dans ngOnDestroy)
   */
  cleanup(): void {
    this.stopAllTimers();
  }
}
