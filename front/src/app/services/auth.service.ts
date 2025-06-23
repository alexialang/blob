import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import {tap, map, catchError} from 'rxjs/operators';
import {Observable, throwError} from 'rxjs';
import { environment } from '../../environments/environment';

interface LoginResponse {
  token: string;
}

@Injectable({ providedIn: 'root' })
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
  }

  getToken(): string | null {
    return localStorage.getItem('JWT_TOKEN');
  }

  isLoggedIn(): boolean {
    return !!this.getToken();
  }
  register(
    email: string,
    password: string,
    firstName: string,
    lastName: string
  ): Observable<any> {
    return this.http.post(
      `${this.base}/user-create`,
      { email, password, firstName, lastName }
    );
  }
}

