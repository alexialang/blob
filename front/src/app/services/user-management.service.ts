import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { map, switchMap } from 'rxjs/operators';

@Injectable({ providedIn: 'root' })
export class UserManagementService {
  private readonly baseUrl = environment.apiBaseUrl;

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  getUsers(page: number = 1, limit: number = 20, search?: string, sort: string = 'email'): Observable<any> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }

        let params = `page=${page}&limit=${limit}&sort=${sort}`;
        if (search && search.trim()) {
          params += `&search=${encodeURIComponent(search.trim())}`;
        }

        return this.http.get<any>(`${this.baseUrl}/admin/all?${params}`);
      }),
      switchMap(apiCall => apiCall)
    );
  }

  getAllUsers(): Observable<any[]> {
    return this.getUsers(1, 1000).pipe(
      map(response => response.data || [])
    );
  }
  anonymizeUser(id: number): Observable<void> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.patch<void>(`${this.baseUrl}/user/${id}/anonymize`, {});
      }),
      switchMap(apiCall => apiCall)
    );
  }

  updateUserRoles(userId: number, roles: string[], permissions: string[]): Observable<any> {
    return this.authService.hasPermission('MANAGE_USERS').pipe(
      map(hasPermission => {
        if (!hasPermission) {
          throw new Error('Permission MANAGE_USERS requise');
        }
        return this.http.put<any>(`${this.baseUrl}/user-permission/user/${userId}`, {
          roles,
          permissions
        });
      }),
      switchMap(apiCall => apiCall)
    );
  }

  isAdmin(): Observable<boolean> {
    return this.authService.isAdmin();
  }
}
