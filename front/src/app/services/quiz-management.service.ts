import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { map, switchMap, catchError } from 'rxjs/operators';

@Injectable({
  providedIn: 'root',
})
export class QuizManagementService {
  private readonly apiUrl = environment.apiBaseUrl;

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  getQuizzes(
    page: number = 1,
    limit: number = 20,
    search?: string,
    sort: string = 'id'
  ): Observable<any> {
    return this.authService.hasPermission('CREATE_QUIZ').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission CREATE_QUIZ requise');
        }

        let params = `page=${page}&limit=${limit}&sort=${sort}`;
        if (search && search.trim()) {
          params += `&search=${encodeURIComponent(search.trim())}`;
        }

        return this.http.get<any>(`${this.apiUrl}/quiz/management/list?${params}`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  getAllQuizzes(): Observable<any[]> {
    return this.getQuizzes(1, 1000).pipe(map(response => response.data || []));
  }

  getOrganizedQuizzes(): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/quiz/organized`);
  }

  getQuiz(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/quiz/${id}`);
  }

  getQuizForEdit(id: number): Observable<any> {
    return this.authService.hasPermission('CREATE_QUIZ').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission CREATE_QUIZ requise');
        }
        return this.http.get(`${this.apiUrl}/quiz/${id}/edit`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  createQuiz(data: any): Observable<any> {
    return this.authService.hasPermission('CREATE_QUIZ').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission CREATE_QUIZ requise');
        }
        return this.http.post(`${this.apiUrl}/quiz/create`, data);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  updateQuiz(id: number, data: any): Observable<any> {
    return this.authService.hasPermission('CREATE_QUIZ').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission CREATE_QUIZ requise');
        }
        return this.http.put(`${this.apiUrl}/quiz/${id}`, data);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  deleteQuiz(id: number): Observable<any> {
    return this.authService.hasPermission('CREATE_QUIZ').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission CREATE_QUIZ requise');
        }
        return this.http.delete(`${this.apiUrl}/quiz/${id}`);
      }),
      switchMap(apiCall => apiCall),
      catchError((error: any) => {
        if (error.status === 401) {
          throw new Error(
            "Non autorisé - Vérifiez vos permissions et votre appartenance à l'entreprise"
          );
        } else if (error.status === 403) {
          throw new Error("Accès interdit - Ce quiz n'appartient pas à votre entreprise");
        } else if (error.status === 404) {
          throw new Error('Quiz non trouvé');
        } else {
          throw new Error(`Erreur serveur: ${error.message || 'Erreur inconnue'}`);
        }
      })
    );
  }

  getTypeQuestions(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/type-question/list`);
  }

  getCategories(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/category-quiz`);
  }

  getGroups(): Observable<any[]> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.get<any[]>(`${this.apiUrl}/group`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  getStatuses(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/status/list`);
  }
}
