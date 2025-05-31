import { Routes } from '@angular/router';
import { authGuard } from './guards/auth.guard';

import { LoginComponent }             from './pages/login/login.component';
import { UserManagementComponent }    from './pages/user-management/user-management.component';
import { CompanyManagementComponent } from './pages/company-management/company-management.component';
import {RegistrationComponent}        from './pages/registration/registration.component';
import {LegalNoticesComponent} from './pages/legal-notices/legal-notices.component';

export const routes: Routes = [
  { path: 'connexion', component: LoginComponent, data: { hideNavbar: true } },
  { path: 'inscription', component: RegistrationComponent,data: { hideNavbar: true } },

  {
    path: 'gestion-utilisateur',
    component: UserManagementComponent,
    canActivate: [authGuard],
  },
  {
    path: 'gestion-entreprise',
    component: CompanyManagementComponent,
    canActivate: [authGuard],
  },
  {path: 'mentions-legales', component: LegalNoticesComponent, data: { hideNavbar: true } },

  { path: '',   redirectTo: 'gestion-utilisateur', pathMatch: 'full' },
  { path: '**', redirectTo: 'gestion-utilisateur' },
];
