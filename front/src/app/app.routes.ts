import { Routes } from '@angular/router';
import { authGuard } from './guards/auth.guard';
import { adminGuard } from './guards/admin.guard';
import { createQuizGuard, manageUsersGuard, viewResultsGuard, companyDetailsGuard } from './guards/permission.guard';
import { quizAccessGuard } from './guards/quiz-access.guard';

import { LoginComponent }             from './pages/login/login.component';
import { UserManagementComponent }    from './pages/user-management/user-management.component';
import { CompanyManagementComponent } from './pages/company-management/company-management.component';
import {RegistrationComponent}        from './pages/registration/registration.component';
import {LegalNoticesComponent}        from './pages/legal-notices/legal-notices.component';
import {ConfirmAccountComponent}      from './pages/confirm-account/confirm-account.component';
import {ForgotPasswordComponent}      from './pages/forgot-password/forgot-password.component';
import {ResetPasswordComponent}       from './pages/reset-password/reset-password.component';
import {AboutComponent}               from './pages/about/about.component';
import {QuizManagementComponent}      from './pages/quiz-management/quiz-management.component';
import {QuizCreationComponent}        from './pages/quiz-creation/quiz-creation.component';
import {CompanyDetailsComponent} from './pages/company-details/company-details.component';
import {QuizCardsComponent} from './pages/quiz-cards/quiz-cards.component';
import {QuizGameComponent} from './pages/quiz-game/quiz-game.component';
import {UserProfileComponent} from './pages/user-profile/user-profile.component';
import {AvatarSelectionComponent} from './pages/avatar-selection/avatar-selection.component';
import {LeaderboardComponent} from './pages/leaderboard/leaderboard.component';
import {MultiplayerRoomCreateComponent} from './pages/multiplayer-room-create/multiplayer-room-create.component';
import {MultiplayerRoomComponent} from './pages/multiplayer-room/multiplayer-room.component';
import {MultiplayerGameComponent} from './pages/multiplayer-game/multiplayer-game.component';
import {QuizResultsComponent} from './pages/quiz-results/quiz-results.component';
import {DonationComponent} from './pages/donation/donation.component';
import {NotFoundComponent} from './pages/not-found/not-found.component';

export const routes: Routes = [
  { path: 'connexion', component: LoginComponent, data: { hideNavbar: true } },

  { path: 'inscription', component: RegistrationComponent,data: { hideNavbar: true } },

  {
    path: 'gestion-utilisateur',
    component: UserManagementComponent,
    canActivate: [authGuard, manageUsersGuard],
  },
  {
    path: 'gestion-entreprise',
    component: CompanyManagementComponent,
    canActivate: [authGuard, manageUsersGuard],
  },
  {
    path: 'gestion-quiz',
    component: QuizManagementComponent,
    canActivate: [authGuard, createQuizGuard],
  },
  {
    path: 'creation-quiz',
    component: QuizCreationComponent,
    canActivate: [authGuard, createQuizGuard],
  },
  {
    path: 'creation-quiz/:id',
    component: QuizCreationComponent,
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
    component: AvatarSelectionComponent,
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
    component: QuizGameComponent,
    canActivate: [quizAccessGuard],
    data: { hideNavbar: true }
  },
  {
    path: 'quiz/:id/results',
    component: QuizResultsComponent,
    canActivate: [authGuard, viewResultsGuard],
    data: { hideNavbar: true }
  },
  {
    path: 'multiplayer/create/:id',
    component: MultiplayerRoomCreateComponent,
    canActivate: [authGuard, createQuizGuard],
    data: { hideNavbar: true }
  },
  {
    path: 'multiplayer/room/:id',
    component: MultiplayerRoomComponent,
    canActivate: [authGuard],
    data: { hideNavbar: true }
  },
  {
    path: 'multiplayer/game/:id',
    component: MultiplayerGameComponent,
    canActivate: [authGuard],
    data: { hideNavbar: true }
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
  {
    path: 'a-propos', data: { hideNavbar: true },
    component: AboutComponent,
  },
  {
    path: 'faire-un-don', data: { hideNavbar: true },
    component: DonationComponent,
  },
  {
    path: 'company/:id',
    component: CompanyDetailsComponent,
    canActivate: [authGuard, companyDetailsGuard]
  },

  { path: '',  component: LoginComponent, pathMatch: 'full', data: { hideNavbar: true } },
  { path: '**', component: NotFoundComponent },
];
