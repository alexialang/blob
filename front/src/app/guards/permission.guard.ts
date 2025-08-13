import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { map, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

export function createPermissionGuard(permission: string): CanActivateFn {
  return (route, state) => {
    const auth = inject(AuthService);
    const router = inject(Router);

    if (!auth.isLoggedIn()) {
      router.navigate(['/connexion']);
      return false;
    }

    return auth.hasPermission(permission).pipe(
      map(hasPermission => {
        if (!hasPermission) {
          router.navigate(['/accueil']);
          return false;
        }
        return true;
      }),
      catchError(() => {
        router.navigate(['/connexion']);
        return of(false);
      })
    );
  };
}

export const createQuizGuard = createPermissionGuard('CREATE_QUIZ');
export const manageUsersGuard = createPermissionGuard('MANAGE_USERS');
export const viewResultsGuard = createPermissionGuard('VIEW_RESULTS');
