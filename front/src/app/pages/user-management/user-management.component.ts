import {
  Component,
  OnInit,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';

import { TuiTable } from '@taiga-ui/addon-table';
import { TuiCell } from '@taiga-ui/layout';
import {
  TuiAvatar,
  TuiBadge,
  TuiCheckbox,
  TuiItemsWithMore,
} from '@taiga-ui/kit';
import {
  TuiChevron,
} from '@taiga-ui/kit';
import {
  TuiDialogService,
  TuiAlertService,
  TuiDropdown,
  TuiGroup,
  TuiButton,
  TuiDataList,
} from '@taiga-ui/core';

import { catchError, of } from 'rxjs';

import { UserManagementService } from '../../services/user-management.service';
import { PaginationComponent } from '../../components/pagination/pagination.component';
import { UserRolesModalComponent, UserWithRoles, UserRole } from '../../components/user-roles-modal/user-roles-modal.component';

type TuiSizeS = 's' | 'm';

interface UserRow {
  id: number;
  selected: boolean;
  avatar: string;
  firstName: string;
  lastName: string;
  name: string;
  email: string;
  active: boolean;
  organization: string;
  group: string;

  rights: string[];
  roles: string[];
  permissions: string[];
  joinedAt?: string;
}

@Component({
  selector: 'app-user-management',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    TuiTable,
    TuiCell,
    TuiAvatar,
    TuiCheckbox,
    TuiItemsWithMore,
    TuiBadge,
    TuiButton,
    TuiDataList,
    TuiGroup,
    TuiDropdown,
    TuiChevron,
    PaginationComponent,
    UserRolesModalComponent,
  ],
  providers: [
  ],
  templateUrl: './user-management.component.html',
  styleUrls: ['./user-management.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UserManagementComponent implements OnInit {
  readonly sizes: TuiSizeS[] = ['m', 's'];
  size: TuiSizeS = 'm';

  private allRows: UserRow[] = [];
  public rows: UserRow[] = [];
  public loadError = false;

  public page = 1;
  public pageSize = 20;

  public filterOrg = '';
  public filterRight = '';
  public filterKeyword = '';
  public orgOptions: string[] = [];
  public rightsOptions: string[] = [];

  public sortColumn: keyof UserRow | '' = '';
  public sortDirection: 'asc' | 'desc' = 'asc';

  public open = false;
  public items = ['Ajouter une entreprise', 'Attribuer une entreprise'];

  public showRolesModal = false;
  public selectedUserForRoles: UserWithRoles | null = null;
  public isDeleting = false; // Protection contre les appels multiples
  public availableRoles: UserRole[] = [
    {
      id: 1,
      name: 'ROLE_USER',
      description: 'Accès utilisateur standard',
      permissions: []
    },
    {
      id: 2,
      name: 'ROLE_ADMIN',
      description: 'Accès administrateur complet',
      permissions: ['CREATE_QUIZ', 'MANAGE_USERS', 'VIEW_RESULTS']
    }
  ];
  public availablePermissions: string[] = ['CREATE_QUIZ', 'MANAGE_USERS', 'VIEW_RESULTS'];

  constructor(
    private userService: UserManagementService,
    private dialogService: TuiDialogService,
    private cdr: ChangeDetectorRef,
    private alerts: TuiAlertService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.generateRandomColor();
    this.loadUsers();
  }

  loadUsers(): void {
    this.userService
      .getUsers()
      .pipe(
        catchError(err => {
          this.loadError = true;
          return of([]);
        })
      )
      .subscribe((users: any[]) => {
        this.allRows = users.map(u => {
          const joinDaysAgo = Math.floor(Math.random() * 365) + 1;

          return {
            id: u.id,
            selected: false,
            avatar: u.name ?? u.email,
            firstName: u.firstName ?? '—',
            lastName: u.lastName ?? '—',
            name: `${u.firstName ?? ''} ${u.lastName ?? ''}`.trim(),
            email: u.email ?? '—',
            active: u.isActive,
            organization: u.companyName ?? '—',
            group:
              Array.isArray(u.groups) && u.groups.length
                ? u.groups.map((g: any) => g.name).join(', ')
                : '—',

            rights: (u.userPermissions ?? []).map((p: any) => p.permission),
            roles: u.roles ?? ['ROLE_USER'],
            permissions: (u.userPermissions ?? []).map((p: any) => p.permission),

            joinedAt: new Date(Date.now() - joinDaysAgo * 24 * 60 * 60 * 1000).toLocaleDateString('fr-FR')

          };
        });
        this.orgOptions = Array.from(new Set(this.allRows.map(r => r.organization))).sort();
        this.rightsOptions = Array.from(new Set(this.allRows.flatMap(r => r.rights))).sort();
        this.applyFilters();
      });
  }
  highlightColor: string = '';

  private generateRandomColor(): void {
    const colors = [
      '#257D54', '#FAA24B', '#D30D4C',
    ];

    const index = Math.floor(Math.random() * colors.length);
    this.highlightColor = colors[index];
  }
  getDeleteButtonText(): string {
    const count = this.getSelectedCount();
    if (count === 0) return 'Supprimer les éléments';
    if (count === 1) return 'Supprimer 1 élément';
    return `Supprimer ${count} éléments`;
  }

  getSelectedCount(): number {
    return this.rows.filter(row => row.selected).length;
  }


  applyFilters(): void {
    let filtered = this.allRows;
    if (this.filterOrg) {
      filtered = filtered.filter(r => r.organization === this.filterOrg);
    }
    if (this.filterRight) {
      filtered = filtered.filter(r => r.rights.includes(this.filterRight));
    }
    if (this.filterKeyword) {
      const kw = this.filterKeyword.toLowerCase();
      filtered = filtered.filter(r =>
        `${r.firstName} ${r.lastName}`.toLowerCase().includes(kw) ||
        r.email.toLowerCase().includes(kw)
      );
    }
    this.rows = filtered;
    this.applySort();
    this.page = 1;
    this.cdr.markForCheck();
  }

  sortBy(column: keyof UserRow): void {
    this.sortDirection = this.sortColumn === column
      ? (this.sortDirection === 'asc' ? 'desc' : 'asc')
      : 'asc';
    this.sortColumn = column;
    this.applySort();
  }

  private applySort(): void {
    if (!this.sortColumn) return;
    const { sortColumn: col, sortDirection: dir } = this;
    this.rows.sort((a, b) => {
      const aVal = a[col], bVal = b[col];
      if (typeof aVal === 'string' && typeof bVal === 'string') {
        return dir === 'asc'
          ? aVal.localeCompare(bVal)
          : bVal.localeCompare(aVal);
      }
      if (typeof aVal === 'number' && typeof bVal === 'number') {
        return dir === 'asc' ? aVal - bVal : bVal - aVal;
      }
      if (typeof aVal === 'boolean' && typeof bVal === 'boolean') {
        return dir === 'asc'
          ? Number(aVal) - Number(bVal)
          : Number(bVal) - Number(aVal);
      }
      return 0;
    });
    this.cdr.markForCheck();
  }

  get pagedRows(): UserRow[] {
    const start = (this.page - 1) * this.pageSize;
    return this.rows.slice(start, start + this.pageSize);
  }

  onPageChange(newPage: number): void {
    this.page = newPage;
    this.cdr.markForCheck();
  }

  get hasSelection(): boolean {
    return this.rows.some(r => r.selected);
  }
  get allSelected(): boolean {
    return this.rows.length > 0 && this.rows.every(r => r.selected);
  }
  toggleAll(checked: boolean): void {
    this.rows.forEach(r => r.selected = checked);
    this.cdr.markForCheck();
  }

  deleteSelected(): void {
    const toDelete = this.rows.filter(r => r.selected).map(r => r.id);
    if (toDelete.length) {
      this.confirmDelete(toDelete);
    }
  }

  deleteSingle(id: number): void {
    this.confirmDelete([id]);
  }

  confirmDelete(ids: number[]): void {
    if (this.isDeleting) {
      return;
    }

    const count = ids.length;

    const confirmed = window.confirm(`Anonymiser ${count} utilisateur${count > 1 ? 's' : ''} ? (Les données seront conservées mais l'utilisateur sera désactivé)`);

    if (!confirmed) {
      return;
    }

    this.isDeleting = true;

    this.userService.anonymizeUser(ids[0]).subscribe({
              next: () => {
          this.isDeleting = false;

        this.loadUsers();

        this.rows.forEach(r => r.selected = false);
        this.cdr.markForCheck();

        this.alerts.open('Utilisateur anonymisé avec succès', {
          label: 'Info',
          appearance: 'positive',
          autoClose: 2000,
        }).subscribe();
      },
      error: (err) => {
        this.isDeleting = false;

        this.alerts.open('Échec de l\'anonymisation', {
          label: 'Erreur',
          appearance: 'danger',
          autoClose: 2000,
        }).subscribe();
      },
    });
  }

  onClick(): void {
    this.open = false;
  }

  viewUserProfile(userId: number): void {
    this.router.navigate(['/profil', userId]);
  }

  openRolesModal(row: UserRow): void {
    this.selectedUserForRoles = {
      id: row.id,
      name: row.name,
      email: row.email,
      roles: row.roles,
      permissions: row.permissions
    };
    this.showRolesModal = true;
    this.cdr.markForCheck();
  }

  closeRolesModal(): void {
    this.showRolesModal = false;
    this.selectedUserForRoles = null;
    this.cdr.markForCheck();
  }



  saveUserRoles(changes: { userId: number; roles: string[]; permissions: string[] }): void {
    this.userService.updateUserRoles(changes.userId, changes.roles, changes.permissions)
      .subscribe({
        next: (updatedUser) => {
          const rowIndex = this.allRows.findIndex(r => r.id === changes.userId);
          if (rowIndex !== -1) {
            this.allRows[rowIndex].roles = changes.roles;
            this.allRows[rowIndex].permissions = changes.permissions;
            this.allRows[rowIndex].rights = changes.permissions; // Pour la compatibilité avec l'affichage
          }

          this.applyFilters();
          this.closeRolesModal();

          this.alerts.open('Rôles mis à jour avec succès', {
            label: 'Succès',
            appearance: 'positive',
            autoClose: 3000,
          }).subscribe();
        },
        error: (err) => {
          this.alerts.open('Erreur lors de la mise à jour des rôles', {
            label: 'Erreur',
            appearance: 'danger',
            autoClose: 3000,
          }).subscribe();
        }
      });
  }
}
