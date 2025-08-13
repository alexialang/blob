import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, of, throwError } from 'rxjs';
import { tap, catchError, map } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { User } from '../models/user.interface';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private readonly base = environment.apiBaseUrl;

  constructor(private http: HttpClient) {
  }

  login(email: string, password: string): Observable<void> {
    return this.http
      .post<{ token: string; refresh_token: string }>(`${this.base}/login_check`, {email, password})
      .pipe(
        tap(res => {
          localStorage.setItem('JWT_TOKEN', res.token);
          localStorage.setItem('REFRESH_TOKEN', res.refresh_token);
          this.clearGuestMode();
        }),
        map(() => {
          return void 0;
        }),
        catchError(error => {
          return throwError(() => error);
        })
      );
  }

  refresh(): Observable<void> {
    const refreshToken = localStorage.getItem('REFRESH_TOKEN');
    if (!refreshToken) {
      return throwError(() => new Error('No refresh token'));
    }
    return this.http
      .post<{ token: string; refresh_token: string }>(`${this.base}/token/refresh`, {refresh_token: refreshToken})
      .pipe(
        tap(res => {
          localStorage.setItem('JWT_TOKEN', res.token);
          localStorage.setItem('REFRESH_TOKEN', res.refresh_token);
        }),
        map(() => void 0)
      );
  }

  logout(): void {
    localStorage.removeItem('JWT_TOKEN');
    localStorage.removeItem('REFRESH_TOKEN');
    this.clearGuestMode();
  }

  getToken(): string | null {
    return localStorage.getItem('JWT_TOKEN');
  }

  isLoggedIn(): boolean {
    return !!this.getToken();
  }

  isGuest(): boolean {
    return localStorage.getItem('GUEST_MODE') === 'true';
  }

  setGuestMode(): void {
    localStorage.setItem('GUEST_MODE', 'true');
  }

  clearGuestMode(): void {
    localStorage.removeItem('GUEST_MODE');
  }

  hasRole(role: string): Observable<boolean> {
    return this.getCurrentUser().pipe(
      map((user: User) => user.roles.includes(role))
    );
  }

  hasPermission(permission: string): Observable<boolean> {
    return this.getCurrentUser().pipe(
      map((user: User) => {
        if (user.roles.includes('ROLE_ADMIN')) {
          return true;
        }
        return user.userPermissions?.some((p: any) => p.permission === permission) || false;
      })
    );
  }

  isAdmin(): Observable<boolean> {
    return this.hasRole('ROLE_ADMIN');
  }

  getCurrentUser(): Observable<User> {
    return this.http.get<User>(`${this.base}/user/profile`);
  }
  register(
    email: string,
    password: string,
    firstName: string,
    lastName: string,
    recaptchaToken?: string
  ): Observable<any> {
    return this.http.post(
      `${this.base}/user-create`,
      { email, password, firstName, lastName, recaptchaToken }
    );
  }
}

