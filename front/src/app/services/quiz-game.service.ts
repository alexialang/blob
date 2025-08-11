import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { tap, catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { Quiz } from '../models/quiz.model';

@Injectable({
    providedIn: 'root'
})
export class QuizGameService {
    private readonly apiUrl = environment.apiBaseUrl;

    constructor(private http: HttpClient) { }

    loadQuiz(quizId: number): Observable<Quiz> {
        return this.http.get<Quiz>(`${this.apiUrl}/quiz/${quizId}`);
    }



    saveGameResult(quizId: number, score: number): Observable<any> {
        return this.http.post(`${this.apiUrl}/user-answer/game-result`, {
            quiz_id: quizId,
            total_score: score
        }).pipe(
            tap(response => {
                console.log('Réponse du serveur:', response);
            }),
            catchError(error => {
                return throwError(() => error);
            })
        );
    }
}
