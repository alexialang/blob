import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { map, switchMap } from 'rxjs/operators';

export interface GlobalStatistics {
  totalUsers: number;
  totalQuizzes: number;
  globalAverageScore: number;
  totalGamesPlayed: number;
  topEmployees: EmployeeScore[];
  companyStats: CompanyScore[];
  quizPerformance: QuizPerformance[];
  groupStats: GroupStats[];
}

export interface CompanyStatistics {
  teamScores: QuizScore[];
  groupScores: GroupScores;
}

export interface QuizScore {
  quizTitle: string;
  quizId: number;
  averageScore: number;
  participants: number;
}

export interface GroupScores {
  [groupName: string]: QuizScore[];
}

export interface EmployeeScore {
  id: number;
  pseudo: string;
  firstName: string;
  lastName: string;
  averageScore: number;
  quizzesCompleted: number;
  companyName?: string;
}

export interface CompanyScore {
  id: number;
  name: string;
  averageScore: number;
  totalEmployees: number;
  totalQuizzes: number;
}

export interface QuizPerformance {
  id: number;
  title: string;
  averageScore: number;
  totalAttempts: number;
  category: string;
}

export interface GroupStats {
  id: number;
  name: string;
  description: string;
  companyName: string;
  totalMembers: number;
  averageScore: number;
  totalQuizzes: number;
}

@Injectable({
  providedIn: 'root'
})
export class GlobalStatisticsService {
  private apiUrl = `${environment.apiUrl}/api/global-statistics`;

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  getGlobalStatistics(): Observable<GlobalStatistics> {
    return this.authService.isAdmin().pipe(
      map(isAdmin => {
        if (!isAdmin) {
          throw new Error('Rôle ADMIN requis');
        }
        return this.http.get<GlobalStatistics>(this.apiUrl);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  getCompanyStatistics(companyId: number, timestamp?: number): Observable<CompanyStatistics> {
    return this.authService.hasPermission('VIEW_RESULTS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission VIEW_RESULTS requise');
        }
        const url = timestamp
          ? `${this.apiUrl}/company/${companyId}?t=${timestamp}`
          : `${this.apiUrl}/company/${companyId}`;
        return this.http.get<CompanyStatistics>(url);
      }),
      switchMap(apiCall => apiCall)
    );
  }

}
