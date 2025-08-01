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
    return this.http.get<any[]>(`${this.apiUrl}/quiz/management/list`);
  }

  getPublicQuizzes(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/quiz/list`);
  }

  getOrganizedQuizzes(): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/quiz/organized`);
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

  getTypeQuestions(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/type-question/list`);
  }

  getCategories(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/category-quiz`);
  }

  getGroups(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/group/list`);
  }

  getStatuses(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/status/list`);
  }
}
