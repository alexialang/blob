import { HttpRequest, HttpHandlerFn, HttpErrorResponse, HttpEvent } from '@angular/common/http';
import { Observable, BehaviorSubject, throwError } from 'rxjs';
import { catchError, filter, switchMap, take, finalize } from 'rxjs/operators';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';


let isRefreshing = false;
const refreshSubject = new BehaviorSubject<string | null>(null);

export function AuthInterceptor(
  req: HttpRequest<unknown>,
  next: HttpHandlerFn
): Observable<HttpEvent<unknown>> {
    const publicUrls = [
      'average-rating',
      'public-leaderboard',
      'login_check',
      'token/refresh',
      'quiz/list',
      'quiz/organized',
      'quiz/[0-9]+$',
      'quiz/[0-9]+/average-rating',
      'quiz/[0-9]+/public-leaderboard',
      'category-quiz',
      'user-create',
      'confirmation-compte',
      'forgot-password',
      'reset-password',
      'donations'
    ];

    const isPublicUrl = publicUrls.some(url => {
      if (url.includes('[0-9]+')) {
        const regex = new RegExp(url.replace(/\[0-9\]\+/g, '\\d+'));
        return regex.test(req.url);
      }
      return req.url.includes(url);
    });

    if (isPublicUrl) {
      return next(req);
    }

    const auth = inject(AuthService);
    const token = auth.getToken();
    let authReq = req;
    if (token) {
      authReq = addTokenHeader(req, token);
    }

    return next(authReq).pipe(
      catchError(err => {
        if (
          err instanceof HttpErrorResponse &&
          err.status === 401 &&
          !req.url.endsWith('/token/refresh') &&
          !req.url.endsWith('/login_check')
        ) {
          console.error('🔐 Token expiré détecté pour:', req.url);
          console.error('🔐 Tentative de refresh automatique...');
          return handle401Error(authReq, next);
        }
        return throwError(() => err);
      })
    );
  }

function addTokenHeader(
  request: HttpRequest<unknown>,
  token: string
): HttpRequest<unknown> {
  return request.clone({
    setHeaders: { Authorization: `Bearer ${token}` }
  });
}

function handle401Error(
  request: HttpRequest<unknown>,
  next: HttpHandlerFn
): Observable<HttpEvent<unknown>> {
  const auth = inject(AuthService);
  if (!isRefreshing) {
    isRefreshing = true;
    refreshSubject.next(null);

      return auth.refresh().pipe(
        switchMap(() => {
          const newToken = auth.getToken();
          if (newToken) {
            console.log('✅ Refresh réussi, nouveau token obtenu');
            refreshSubject.next(newToken);
            return next(addTokenHeader(request, newToken));
          }
          console.error('❌ Pas de token après refresh, déconnexion');
          auth.logout();
          return throwError(() => new Error('No token after refresh'));
        }),
        catchError(err => {
          console.error('❌ Erreur lors du refresh:', err);
          auth.logout();
          return throwError(() => err);
        }),
        finalize(() => {
          console.log('🔄 Fin du processus de refresh');
          isRefreshing = false;
        })
      );
    } else {
      return refreshSubject.pipe(
        filter(token => token !== null),
        take(1),
        switchMap(token => next(addTokenHeader(request, token!)))
      );
    }
  }
