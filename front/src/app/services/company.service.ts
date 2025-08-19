import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

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
  providedIn: 'root'
})
export class CompanyService {

  private apiUrl = `${environment.apiBaseUrl}/companies`;

  constructor(private http: HttpClient) { }

  getCompanies(): Observable<Company[]> {
    return this.http.get<Company[]>(this.apiUrl);
  }

  getCompany(id: number): Observable<Company> {
    return this.http.get<Company>(`${this.apiUrl}/${id}`);
  }

  createCompany(company: Partial<Company>): Observable<Company> {
    return this.http.post<Company>(this.apiUrl, company);
  }

  updateCompany(id: number, company: Partial<Company>): Observable<Company> {
    return this.http.put<Company>(`${this.apiUrl}/${id}`, company);
  }

  deleteCompany(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }

  exportCompaniesCsv(): Observable<string> {
    return this.http.get(`${this.apiUrl}/export/csv`, { responseType: 'text' });
  }

  exportCompaniesJson(): Observable<any> {
    return this.http.get(`${this.apiUrl}/export/json`);
  }

  importCompaniesCsv(file: File): Observable<ImportResult> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post<ImportResult>(`${this.apiUrl}/import/csv`, formData);
  }

  getCompanyStats(id: number): Observable<CompanyStats> {
    return this.http.get<CompanyStats>(`${this.apiUrl}/${id}/stats`);
  }

  assignUserToCompany(companyId: number, userId: number, roles: string[] = ['ROLE_USER'], permissions: string[] = []): Observable<AssignUserResult> {
    return this.http.post<AssignUserResult>(`${this.apiUrl}/${companyId}/assign-user`, {
      userId,
      roles,
      permissions
    });
  }

  getAvailableUsers(companyId: number): Observable<AvailableUser[]> {
    return this.http.get<AvailableUser[]>(`${this.apiUrl}/${companyId}/available-users`);
  }

  getCompanyUsers(companyId: number): Observable<CompanyUser[]> {
    return this.http.get<CompanyUser[]>(`${this.apiUrl}/${companyId}/users`);
  }

  getCompanyGroups(companyId: number): Observable<CompanyGroup[]> {
    return this.http.get<CompanyGroup[]>(`${this.apiUrl}/${companyId}/groups`);
  }

  createGroup(companyId: number, groupData: { name: string; description: string; userIds: number[] }): Observable<any> {
    return this.http.post<any>(`${environment.apiBaseUrl}/group`, {
      name: groupData.name,
      acces_code: groupData.description,
      company_id: companyId,
      member_ids: groupData.userIds
    });
  }

  deleteGroup(companyId: number, groupId: number): Observable<any> {
    return this.http.delete<any>(`${environment.apiBaseUrl}/group/${groupId}`);
  }

  addUserToGroup(companyId: number, groupId: number, userId: number): Observable<any> {
    return this.http.post<any>(`${environment.apiBaseUrl}/group/${groupId}/add-user`, { user_id: userId });
  }

  removeUserFromGroup(companyId: number, groupId: number, userId: number): Observable<any> {
    return this.http.delete<any>(`${environment.apiBaseUrl}/group/${groupId}/remove-user/${userId}`);
  }
}
