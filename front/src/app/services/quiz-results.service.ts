import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, tap } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';

export interface QuizResult {
  quizId: number;
  score: number;
}

export interface QuizRating {
  quizId: number;
  rating: number;
}

export interface LeaderboardEntry {
  rank: number;
  name: string;
  company: string;
  score: number;
  isCurrentUser: boolean;
}

export interface QuizLeaderboard {
  leaderboard: LeaderboardEntry[];
  currentUserRank: number;
  totalPlayers: number;
  currentUserScore: number;
}

@Injectable({
  providedIn: 'root',
})
export class QuizResultsService {
  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  saveQuizResult(result: QuizResult): Observable<any> {
    if (this.authService.isGuest()) {
      sessionStorage.setItem('guest-quiz-score', result.score.toString());
      return of({ success: true, saved: false, score: result.score });
    }

    return this.http.post(`${environment.apiBaseUrl}/user-answer/game-result`, {
      quiz_id: result.quizId,
      total_score: result.score,
    });
  }

  rateQuiz(rating: QuizRating): Observable<any> {
    return this.http.post(`${environment.apiBaseUrl}/user-answer/rate-quiz`, rating);
  }

  getQuizLeaderboard(quizId: number): Observable<QuizLeaderboard> {
    console.log('=== DEBUG GET QUIZ LEADERBOARD ===');
    console.log('quizId:', quizId);
    console.log('isGuest():', this.authService.isGuest());
    
    if (this.authService.isGuest()) {
      console.log('Mode GUEST - utilisation de getGuestLeaderboardWithRealData');
      return this.getGuestLeaderboardWithRealData(quizId);
    }

    // Utiliser l'endpoint privé pour les utilisateurs connectés
    return this.http.get<QuizLeaderboard>(
      `${environment.apiBaseUrl}/quiz/${quizId}/leaderboard`
    );
  }

  private getGuestLeaderboardWithRealData(quizId: number): Observable<QuizLeaderboard> {
    const savedScore = sessionStorage.getItem('guest-quiz-score');
    const guestScore = savedScore ? parseInt(savedScore) : 0;

    return this.getPublicQuizLeaderboard(quizId).pipe(
      map((publicData: any) => {
        const realLeaderboard = publicData.leaderboard || [];

        // Créer le leaderboard final avec le guest inséré au bon endroit
        const finalLeaderboard = [...realLeaderboard];
        
        // Ajouter le guest au leaderboard
        finalLeaderboard.push({
          rank: 0, // Temporaire, sera recalculé
          name: 'Vous (Visiteur)',
          company: 'Non connecté',
          score: guestScore,
          isCurrentUser: true,
        });

        // Trier par score décroissant
        finalLeaderboard.sort((a, b) => b.score - a.score);

        // Recalculer tous les rangs
        finalLeaderboard.forEach((player, index) => {
          player.rank = index + 1;
        });

        // Trouver le rang du guest
        const guestEntry = finalLeaderboard.find(player => player.isCurrentUser);
        const guestRank = guestEntry ? guestEntry.rank : finalLeaderboard.length;

        const displayLeaderboard = finalLeaderboard.slice(0, 5);

        if (guestRank > 5) {
          if (guestEntry) {
            displayLeaderboard.push(guestEntry);
          }
        }

        const leaderboardData: QuizLeaderboard = {
          leaderboard: displayLeaderboard,
          currentUserRank: guestRank,
          totalPlayers: finalLeaderboard.length,
          currentUserScore: guestScore,
        };

        return leaderboardData;
      })
    );
  }

  getGeneralLeaderboard(): Observable<any> {
    return this.http.get(`${environment.apiBaseUrl}/leaderboard`);
  }

  getQuizRating(quizId: number): Observable<any> {
    return this.http.get(`${environment.apiBaseUrl}/user-answer/quiz/${quizId}/rating`);
  }

  getPublicQuizRating(quizId: number): Observable<any> {
    return this.http.get(`${environment.apiBaseUrl}/quiz/${quizId}/average-rating`);
  }

  getPublicQuizLeaderboard(quizId: number): Observable<any> {
    return this.http.get(`${environment.apiBaseUrl}/quiz/${quizId}/public-leaderboard`);
  }
}
