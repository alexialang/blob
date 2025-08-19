import { Injectable } from '@angular/core';
import {
  HttpInterceptor,
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpErrorResponse
} from '@angular/common/http';
import { Observable, BehaviorSubject, throwError } from 'rxjs';
import { catchError, filter, switchMap, take, finalize } from 'rxjs/operators';
import { AuthService } from '../services/auth.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private isRefreshing = false;
  private refreshSubject = new BehaviorSubject<string | null>(null);

  constructor(private auth: AuthService) {}

  intercept(
    req: HttpRequest<any>,
    next: HttpHandler
  ): Observable<HttpEvent<any>> {
    const publicUrls = [
      'average-rating',
      'public-leaderboard',
      'login_check',
      'token/refresh',
      'quiz/list'
    ];

    const isPublicUrl = publicUrls.some(url => req.url.includes(url));
    
    if (isPublicUrl) {
      return next.handle(req);
    }

    const token = this.auth.getToken();
    let authReq = req;
    if (token) {
      authReq = this.addTokenHeader(req, token);
    }

    return next.handle(authReq).pipe(
      catchError(err => {
        if (
          err instanceof HttpErrorResponse &&
          err.status === 401 &&
          !req.url.endsWith('/token/refresh') &&
          !req.url.endsWith('/login_check')
        ) {
          return this.handle401Error(authReq, next);
        }
        return throwError(() => err);
      })
    );
  }

  private addTokenHeader(
    request: HttpRequest<any>,
    token: string
  ): HttpRequest<any> {
    return request.clone({
      setHeaders: { Authorization: `Bearer ${token}` }
    });
  }

  private handle401Error(
    request: HttpRequest<any>,
    next: HttpHandler
  ): Observable<HttpEvent<any>> {
    if (!this.isRefreshing) {
      this.isRefreshing = true;
      this.refreshSubject.next(null);

      return this.auth.refresh().pipe(
        switchMap(() => {
          const newToken = this.auth.getToken();
          if (newToken) {
            this.refreshSubject.next(newToken);
            return next.handle(this.addTokenHeader(request, newToken));
          }
          this.auth.logout();
          return throwError(() => new Error('No token after refresh'));
        }),
        catchError(err => {
          this.auth.logout();
          return throwError(() => err);
        }),
        finalize(() => {
          this.isRefreshing = false;
        })
      );
    } else {
      return this.refreshSubject.pipe(
        filter(token => token !== null),
        take(1),
        switchMap(token => next.handle(this.addTokenHeader(request, token!)))
      );
    }
  }
}
