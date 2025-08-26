import { Injectable } from '@angular/core';

export interface PlayerScore {
  username: string;
  score: number;
  rank: number;
  isCurrentUser: boolean;
  isOnline: boolean;
  lastAnswerCorrect?: boolean;
}


@Injectable({
  providedIn: 'root'
})
export class MultiplayerScoreService {

  /**
   * Normalise un score brut en pourcentage
   */
  normalizeScoreToPercentage(score: number, totalQuestions: number): number {
    if (totalQuestions === 0) return 0;
    return Math.min(Math.round((score / (totalQuestions * 10)) * 100), 100);
  }

  /**
   * Met à jour le classement des joueurs
   */
  updateLeaderboard(players: PlayerScore[]): PlayerScore[] {
    players.sort((a, b) => b.score - a.score);

    players.forEach((player, index) => {
      player.rank = index + 1;
    });

    return players;
  }

  /**
   * Met à jour le score d'un joueur spécifique
   */
  updatePlayerScore(players: PlayerScore[], username: string, points: number, isCorrect: boolean): PlayerScore[] {
    const player = players.find(p => p.username === username);
    if (player) {
      player.score += points;
      player.lastAnswerCorrect = isCorrect;
    }
    return this.updateLeaderboard(players);
  }

  /**
   * Initialise les scores des joueurs à partir des données du jeu
   */
  initializePlayerScores(gameData: any, currentUsername: string): PlayerScore[] {
    return gameData.players.map((player: any) => {
      let playerScore = 0;

      if (gameData.playerScores && gameData.playerScores[player.id] !== undefined) {
        playerScore = gameData.playerScores[player.id];
      } else if (gameData.sharedScores && gameData.sharedScores[player.username] !== undefined) {
        playerScore = gameData.sharedScores[player.username];
      } else if (gameData.leaderboard) {
        const leaderboardEntry = gameData.leaderboard.find((entry: any) => entry.username === player.username);
        if (leaderboardEntry) {
          playerScore = leaderboardEntry.score || 0;
        }
      }

      return {
        username: player.username,
        score: playerScore,
        isCurrentUser: player.username === currentUsername,
        rank: 1, // Sera mis à jour par updateLeaderboard
        isOnline: true,
        lastAnswerCorrect: undefined
      };
    });
  }

  /**
   * Synchronise les scores avec les données serveur
   */
  syncWithServerScores(players: PlayerScore[], serverData: any): { players: PlayerScore[], updated: boolean } {
    let updated = false;

    if (serverData.playerScores) {
      players.forEach(player => {
        const serverPlayer = serverData.players?.find((p: any) => p.username === player.username);
        if (serverPlayer && serverData.playerScores[serverPlayer.id] !== undefined) {
          const serverScore = serverData.playerScores[serverPlayer.id];
          if (serverScore > player.score) {
            player.score = serverScore;
            updated = true;
          }
        }
      });
    }

    if (serverData.sharedScores && Object.keys(serverData.sharedScores).length > 0) {
      players.forEach(player => {
        if (serverData.sharedScores[player.username] !== undefined) {
          const serverScore = serverData.sharedScores[player.username];
          if (serverScore !== player.score && serverScore >= 0) {
            player.score = serverScore;
            updated = true;
          }
        }
      });
    }

    if (serverData.leaderboard && serverData.leaderboard.length > 0) {
      players.forEach(player => {
        const leaderboardEntry = serverData.leaderboard.find((entry: any) => entry.username === player.username);
        if (leaderboardEntry && leaderboardEntry.score !== undefined) {
          const serverScore = leaderboardEntry.score;
          if (serverScore > player.score) {
            player.score = serverScore;
            updated = true;
          }
        }
      });
    }

    if (updated) {
      this.updateLeaderboard(players);
    }

    return { players, updated };
  }

  /**
   * Sauvegarde les scores dans localStorage
   */
  saveScoresToLocalStorage(gameId: string, players: PlayerScore[]): void {
    const storageKey = `game_scores_${gameId}`;
    const allScores: { [username: string]: number } = {};

    players.forEach(player => {
      allScores[player.username] = player.score;
    });

    const nonZeroScores = Object.values(allScores).filter(score => score > 0);
    if (nonZeroScores.length === 0) {
      console.warn('ATTENTION: Tous les scores sont à 0, sauvegarde annulée');
      return;
    }

    localStorage.setItem(storageKey, JSON.stringify(allScores));
  }

  /**
   * Charge les scores depuis localStorage
   */
  loadScoresFromLocalStorage(gameId: string): { [username: string]: number } {
    const storageKey = `game_scores_${gameId}`;
    const savedScores = localStorage.getItem(storageKey);
    return savedScores ? JSON.parse(savedScores) : {};
  }
}
