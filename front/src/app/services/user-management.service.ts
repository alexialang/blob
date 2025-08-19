import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class UserManagementService {
  private readonly baseUrl = `${environment.apiBaseUrl}/user`;

  constructor(private http: HttpClient) {}

  getUsers(): Observable<any[]> {
    return this.http.get<any[]>(`${this.baseUrl}`);
  }
  anonymizeUser(id: number): Observable<void> {
    return this.http.patch<void>(`${this.baseUrl}/${id}/anonymize`, {});
  }

  updateUserRoles(userId: number, roles: string[], permissions: string[]): Observable<any> {
    return this.http.put<any>(`${this.baseUrl}/${userId}/roles`, {
      roles,
      permissions
    });
  }
}
