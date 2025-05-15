import { Routes } from '@angular/router';
import { UserManagementComponent } from './pages/user-management/user-management.component';

export const routes: Routes = [
  {
    path: 'gestion-utilisateur', component: UserManagementComponent,
  },
];
