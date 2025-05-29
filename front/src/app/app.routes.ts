import { Routes } from '@angular/router';
import { authGuard } from './guards/auth.guard';

import { LoginComponent }             from './pages/login/login.component';
import { UserManagementComponent }    from './pages/user-management/user-management.component';
import { CompanyManagementComponent } from './pages/company-management/company-management.component';

export const routes: Routes = [
  { path: 'login', component: LoginComponent },

  {
    path: 'gestion-utilisateur',
    component: UserManagementComponent,
    canActivate: [authGuard],
  },
  {
    path: 'company-management',
    component: CompanyManagementComponent,
    canActivate: [authGuard],
  },

  { path: '',   redirectTo: 'gestion-utilisateur', pathMatch: 'full' },
  { path: '**', redirectTo: 'gestion-utilisateur' },
];
