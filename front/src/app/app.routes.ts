import { Routes } from '@angular/router';
import { authGuard } from './guards/auth.guard';
import { adminGuard } from './guards/admin.guard';
import { createQuizGuard, manageUsersGuard, viewResultsGuard, companyDetailsGuard } from './guards/permission.guard';
import { quizAccessGuard } from './guards/quiz-access.guard';

// Import critical components
import { LoginComponent } from './pages/login/login.component';
import { QuizCardsComponent } from './pages/quiz-cards/quiz-cards.component';
import { NotFoundComponent } from './pages/not-found/not-found.component';
import { RegistrationComponent } from './pages/registration/registration.component';
import { UserProfileComponent } from './pages/user-profile/user-profile.component';
import { LeaderboardComponent } from './pages/leaderboard/leaderboard.component';

export const routes: Routes = [
  { path: 'connexion', component: LoginComponent, data: { hideNavbar: true } },

  { 
    path: 'inscription', 
    component: RegistrationComponent,
    data: { hideNavbar: true } 
  },

  {
    path: 'gestion-utilisateur',
    loadComponent: () => import('./pages/user-management/user-management.component').then(m => m.UserManagementComponent),
    canActivate: [authGuard, manageUsersGuard],
  },
  {
    path: 'gestion-entreprise',
    loadComponent: () => import('./pages/company-management/company-management.component').then(m => m.CompanyManagementComponent),
    canActivate: [authGuard, manageUsersGuard],
  },
  {
    path: 'gestion-quiz',
    loadComponent: () => import('./pages/quiz-management/quiz-management.component').then(m => m.QuizManagementComponent),
    canActivate: [authGuard, createQuizGuard],
  },
  {
    path: 'creation-quiz',
    loadComponent: () => import('./pages/quiz-creation/quiz-creation.component').then(m => m.QuizCreationComponent),
    canActivate: [authGuard, createQuizGuard],
  },
  {
    path: 'creation-quiz/:id',
    loadComponent: () => import('./pages/quiz-creation/quiz-creation.component').then(m => m.QuizCreationComponent),
    canActivate: [authGuard, createQuizGuard],
  },
  {
    path: 'quiz',
    component: QuizCardsComponent,
    canActivate: [quizAccessGuard],
  },
  {
    path: 'profil',
    component: UserProfileComponent,
    canActivate: [authGuard],
  },
  {
    path: 'profil/avatar',
    loadComponent: () => import('./pages/avatar-selection/avatar-selection.component').then(m => m.AvatarSelectionComponent),
    canActivate: [authGuard],
  },
  {
    path: 'profil/:id',
    component: UserProfileComponent,
    canActivate: [authGuard, manageUsersGuard],
  },
  {
    path: 'classement',
    component: LeaderboardComponent,
    canActivate: [authGuard],
  },
  {
    path: 'quiz/:id/play',
    loadComponent: () => import('./pages/quiz-game/quiz-game.component').then(m => m.QuizGameComponent),
    canActivate: [quizAccessGuard],
    data: { hideNavbar: true }
  },
  {
    path: 'quiz/:id/results',
    loadComponent: () => import('./pages/quiz-results/quiz-results.component').then(m => m.QuizResultsComponent),
    canActivate: [authGuard, viewResultsGuard],
    data: { hideNavbar: true }
  },
  {
    path: 'multiplayer/create/:id',
    loadComponent: () => import('./pages/multiplayer-room-create/multiplayer-room-create.component').then(m => m.MultiplayerRoomCreateComponent),
    canActivate: [authGuard, createQuizGuard],
    data: { hideNavbar: true }
  },
  {
    path: 'multiplayer/room/:id',
    loadComponent: () => import('./pages/multiplayer-room/multiplayer-room.component').then(m => m.MultiplayerRoomComponent),
    canActivate: [authGuard],
    data: { hideNavbar: true }
  },
  {
    path: 'multiplayer/game/:id',
    loadComponent: () => import('./pages/multiplayer-game/multiplayer-game.component').then(m => m.MultiplayerGameComponent),
    canActivate: [authGuard],
    data: { hideNavbar: true }
  },
  {
    path: 'mentions-legales', 
    loadComponent: () => import('./pages/legal-notices/legal-notices.component').then(m => m.LegalNoticesComponent), 
    data: { hideNavbar: true } 
  },
  {
    path: 'confirmation-compte/:token',
    loadComponent: () => import('./pages/confirm-account/confirm-account.component').then(m => m.ConfirmAccountComponent)
  },
  {
    path: 'mot-de-passe-oublie', 
    data: { hideNavbar: true },
    loadComponent: () => import('./pages/forgot-password/forgot-password.component').then(m => m.ForgotPasswordComponent),
  },
  {
    path: 'reset-password/:token',
    loadComponent: () => import('./pages/reset-password/reset-password.component').then(m => m.ResetPasswordComponent),
    data: { hideNavbar: true },
  },
  {
    path: 'a-propos', 
    data: { hideNavbar: true },
    loadComponent: () => import('./pages/about/about.component').then(m => m.AboutComponent),
  },
  {
    path: 'faire-un-don', 
    data: { hideNavbar: true },
    loadComponent: () => import('./pages/donation/donation.component').then(m => m.DonationComponent),
  },
  {
    path: 'payment-success', 
    data: { hideNavbar: true },
    loadComponent: () => import('./pages/payment-success/payment-success.component').then(m => m.PaymentSuccessComponent),
  },
  {
    path: 'company/:id',
    loadComponent: () => import('./pages/company-details/company-details.component').then(m => m.CompanyDetailsComponent),
    canActivate: [authGuard, companyDetailsGuard]
  },

  { path: '',  component: LoginComponent, pathMatch: 'full', data: { hideNavbar: true } },
  { path: '**', component: NotFoundComponent },
];
