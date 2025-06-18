import { Routes } from '@angular/router';
import { authGuard } from './guards/auth.guard';

import { LoginComponent }             from './pages/login/login.component';
import { UserManagementComponent }    from './pages/user-management/user-management.component';
import { CompanyManagementComponent } from './pages/company-management/company-management.component';
import {RegistrationComponent}        from './pages/registration/registration.component';
import {LegalNoticesComponent}        from './pages/legal-notices/legal-notices.component';
import {ConfirmAccountComponent}      from './pages/confirm-account/confirm-account.component';
import {ForgotPasswordComponent}      from './pages/forgot-password/forgot-password.component';
import {ResetPasswordComponent}       from './pages/reset-password/reset-password.component';

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
  {
    path: 'confirmation-compte/:token',
    component: ConfirmAccountComponent
  },
  {
    path: 'mot-de-passe-oublie', data: { hideNavbar: true },
    component: ForgotPasswordComponent,
  },
  {
    path: 'reset-password/:token',
    component: ResetPasswordComponent,data: { hideNavbar: true },
  },

  { path: '',   redirectTo: 'gestion-utilisateur', pathMatch: 'full' },
  { path: '**', redirectTo: 'gestion-utilisateur' },
];
