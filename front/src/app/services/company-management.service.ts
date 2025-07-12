import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class CompanyManagementService {
  private readonly apiUrl = environment.apiBaseUrl;

  constructor(private readonly http: HttpClient) {}

  getCompanies(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/companies`);
  }

  getCompany(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/company/${id}`);
  }

  getCompanyDetailed(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/company/${id}?include=users,groups`);
  }

  createCompany(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/company-create`, data);
  }

  updateCompany(id: number, data: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/company/${id}`, data);
  }

  deleteCompany(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/company/${id}`);
  }

  createGroup(groupData: { name: string; description?: string; companyId: number; memberIds?: number[] }): Observable<any> {
    const backendData = {
      name: groupData.name,
      acces_code: groupData.description || '',
      company_id: groupData.companyId,
      member_ids: groupData.memberIds || []
    };
    return this.http.post(`${this.apiUrl}/group`, backendData);
  }

  updateGroup(groupId: number, data: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/group/${groupId}`, data);
  }

  deleteGroup(groupId: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/group/${groupId}`);
  }

  addUserToGroup(groupId: number, userId: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/group/${groupId}/add-user`, { user_id: userId });
  }

  removeUserFromGroup(groupId: number, userId: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/group/${groupId}/remove-user/${userId}`);
  }

  getCompanyWithUsers(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/company/${id}/users`);
  }

  getCompaniesWithGroups(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/companies?include=groups`);
  }
  deleteUser(userId: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/user/${userId}`);
  }


}
