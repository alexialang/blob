import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { map, switchMap } from 'rxjs/operators';

export interface Company {
  id: number;
  name: string;
  userCount?: number;
  groupCount?: number;
  quizCount?: number;
  createdAt?: string;
}

export interface CompanyUser {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  pseudo?: string;
  avatar?: string;
  roles: string[];
  isActive: boolean;
  isVerified: boolean;
  lastAccess?: string;
  dateRegistration?: string;
}

export interface CompanyGroup {
  id: number;
  name: string;
  description?: string;
  userCount: number;
  quizCount: number;
}

export interface CompanyStats {
  id: number;
  name: string;
  userCount: number;
  activeUsers: number;
  groupCount: number;
  quizCount: number;
  createdAt?: string;
  lastActivity?: string;
}

export interface AvailableUser {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  pseudo?: string;
  currentCompany?: {
    id: number;
    name: string;
  };
  roles: string[];
  isVerified: boolean;
}

export interface AssignUserResult {
  success: boolean;
  message: string;
  user?: {
    id: number;
    email: string;
    firstName: string;
    lastName: string;
    roles: string[];
    companyId: number;
    companyName: string;
  };
}

export interface ImportResult {
  success: number;
  errors: string[];
}

@Injectable({
  providedIn: 'root',
})
export class CompanyService {
  private apiUrl = `${environment.apiBaseUrl}/companies`;

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  getCompanies(): Observable<Company[]> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.get<Company[]>(this.apiUrl);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  getCompany(id: number): Observable<Company> {
    return this.http.get<Company>(`${this.apiUrl}/${id}`);
  }

  createCompany(company: Partial<Company>): Observable<Company> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.post<Company>(this.apiUrl, company);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  deleteCompany(id: number): Observable<void> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.delete<void>(`${this.apiUrl}/${id}`);
      }),
      switchMap(apiCall => apiCall)
    );
  }
  exportCompaniesCsv(): Observable<string> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.get(`${this.apiUrl}/export/csv`, { responseType: 'text' });
      }),
      switchMap(apiCall => apiCall)
    );
  }

  exportCompaniesJson(): Observable<any> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.get(`${this.apiUrl}/export/json`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  importCompaniesCsv(file: File): Observable<ImportResult> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        const formData = new FormData();
        formData.append('file', file);
        return this.http.post<ImportResult>(`${this.apiUrl}/import/csv`, formData);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  getCompanyStats(id: number): Observable<CompanyStats> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.get<CompanyStats>(`${this.apiUrl}/${id}/stats`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  assignUserToCompany(
    companyId: number,
    userId: number,
    roles: string[],
    permissions: string[]
  ): Observable<AssignUserResult> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.post<AssignUserResult>(`${this.apiUrl}/${companyId}/assign-user`, {
          userId,
          roles,
          permissions,
        });
      }),
      switchMap(apiCall => apiCall)
    );
  }

  getAvailableUsers(companyId: number): Observable<AvailableUser[]> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.get<AvailableUser[]>(`${this.apiUrl}/${companyId}/available-users`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  getCompanyUsers(companyId: number): Observable<CompanyUser[]> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.get<CompanyUser[]>(`${this.apiUrl}/${companyId}/users`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  getCompanyGroups(companyId: number): Observable<CompanyGroup[]> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.get<CompanyGroup[]>(`${this.apiUrl}/${companyId}/groups`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  createGroup(
    companyId: number,
    groupData: { name: string; description: string; userIds: number[] }
  ): Observable<any> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.post<any>(`${environment.apiBaseUrl}/group`, {
          name: groupData.name,
          acces_code: groupData.description,
          company_id: companyId,
          member_ids: groupData.userIds,
        });
      }),
      switchMap(apiCall => apiCall)
    );
  }

  deleteGroup(companyId: number, groupId: number): Observable<any> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.delete<any>(`${environment.apiBaseUrl}/group/${groupId}`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  addUserToGroup(companyId: number, groupId: number, userId: number): Observable<any> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.post<any>(`${environment.apiBaseUrl}/group/${groupId}/add-user`, {
          user_id: userId,
        });
      }),
      switchMap(apiCall => apiCall)
    );
  }

  removeUserFromGroup(companyId: number, groupId: number, userId: number): Observable<any> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.delete<any>(
          `${environment.apiBaseUrl}/group/${groupId}/remove-user/${userId}`
        );
      }),
      switchMap(apiCall => apiCall)
    );
  }

  getCompanyBasic(id: number): Observable<any> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.get<any>(`${this.apiUrl}/${id}/basic`);
      }),
      switchMap(apiCall => apiCall)
    );
  }
}
