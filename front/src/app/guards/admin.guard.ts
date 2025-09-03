import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { map, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

export const adminGuard: CanActivateFn = (route, state) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (!auth.isLoggedIn()) {
    router.navigate(['/connexion']);
    return false;
  }

  return auth.isAdmin().pipe(
    map(isAdmin => {
      if (!isAdmin) {
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
