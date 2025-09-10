import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map } from 'rxjs/operators';
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
    if (this.authService.isGuest()) {
      return this.getGuestLeaderboardWithRealData(quizId);
    }

    return this.http.get<QuizLeaderboard>(`${environment.apiBaseUrl}/leaderboard/quiz/${quizId}`);
  }

  private getGuestLeaderboardWithRealData(quizId: number): Observable<QuizLeaderboard> {
    const savedScore = sessionStorage.getItem('guest-quiz-score');
    const guestScore = savedScore ? parseInt(savedScore) : 0;

    return this.getPublicQuizLeaderboard(quizId).pipe(
      map((publicData: any) => {
        const realLeaderboard = publicData.leaderboard || [];

        let guestRank = realLeaderboard.length + 1;
        for (let i = 0; i < realLeaderboard.length; i++) {
          if (guestScore > realLeaderboard[i].score) {
            guestRank = i + 1;
            break;
          }
        }

        const finalLeaderboard = [...realLeaderboard];
        finalLeaderboard.splice(guestRank - 1, 0, {
          rank: guestRank,
          name: 'Vous (Visiteur)',
          company: 'Non connecté',
          score: guestScore,
          isCurrentUser: true,
        });

        finalLeaderboard.forEach((player, index) => {
          player.rank = index + 1;
        });

        const displayLeaderboard = finalLeaderboard.slice(0, 5);

        if (guestRank > 5) {
          const guestEntry = finalLeaderboard.find(player => player.isCurrentUser);
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
