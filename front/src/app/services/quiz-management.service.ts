import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class QuizManagementService {
  private readonly apiUrl = environment.apiBaseUrl;

  constructor(private http: HttpClient) {}

  getQuizzes(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/quiz/list`);
  }

  getQuiz(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/quiz/${id}`);
  }

  createQuiz(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/quiz/create`, data);
  }

  updateQuiz(id: number, data: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/quiz/${id}`, data);
  }

  deleteQuiz(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/quiz/${id}`);
  }

  activateQuiz(id: number): Observable<any> {
    return this.http.patch(`${this.apiUrl}/quiz/${id}/activate`, {});
  }

  deactivateQuiz(id: number): Observable<any> {
    return this.http.patch(`${this.apiUrl}/quiz/${id}/deactivate`, {});
  }

  duplicateQuiz(id: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/quiz/${id}/duplicate`, {});
  }


  assignQuizToGroups(quizId: number, groupIds: number[]): Observable<any> {
    return this.http.post(`${this.apiUrl}/quiz/${quizId}/assign-groups`, { groupIds });
  }

  removeQuizFromGroups(quizId: number, groupIds: number[]): Observable<any> {
    return this.http.post(`${this.apiUrl}/quiz/${quizId}/remove-groups`, { groupIds });
  }
}
