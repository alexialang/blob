import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, throwError, of } from 'rxjs';
import { tap, catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { Quiz } from '../models/quiz.model';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root',
})
export class QuizGameService {
  private readonly apiUrl = environment.apiBaseUrl;

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  loadQuiz(quizId: number): Observable<Quiz> {
    return this.http.get<Quiz>(`${this.apiUrl}/quiz/${quizId}`);
  }

  saveGameResult(quizId: number, score: number): Observable<any> {
    if (this.authService.isGuest()) {
      sessionStorage.setItem('guest-quiz-score', score.toString());
      return of({ success: true, saved: false, score });
    }

    return this.http
      .post(`${this.apiUrl}/user-answer/game-result`, {
        quiz_id: quizId,
        total_score: score,
      })
      .pipe(
        catchError(error => {
          return throwError(() => error);
        })
      );
  }
}
