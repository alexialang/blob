import { Routes } from '@angular/router';
import { UserManagementComponent } from './pages/user-management/user-management.component';
import {CompanyManagementComponent} from './pages/company-management/company-management.component';

export const routes: Routes = [
  {
    path: 'gestion-utilisateur',
    component: UserManagementComponent
  },
  {
    path: 'company-management',
    component: CompanyManagementComponent
  },
];
