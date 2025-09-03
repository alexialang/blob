import {
  Component,
  OnInit,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
  OnDestroy,
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

  TuiButton,
  TuiDataList,
} from '@taiga-ui/core';

import { catchError, of, Subject, takeUntil, debounceTime, distinctUntilChanged } from 'rxjs';

import { UserManagementService } from '../../services/user-management.service';
import { AuthService } from '../../services/auth.service';
import { PaginationComponent } from '../../components/pagination/pagination.component';
import { UserRolesModalComponent, UserWithRoles, UserRole } from '../../components/user-roles-modal/user-roles-modal.component';
import { HasPermissionDirective } from '../../directives/has-permission.directive';

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
    TuiBadge,
    TuiCheckbox,
    TuiItemsWithMore,
    TuiButton,
    PaginationComponent,
    UserRolesModalComponent,
    HasPermissionDirective,
  ],
  providers: [],
  templateUrl: './user-management.component.html',
  styleUrls: ['./user-management.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UserManagementComponent implements OnInit, OnDestroy {
  readonly sizes: TuiSizeS[] = ['m', 's'];
  size: TuiSizeS = 'm';

  public rows: UserRow[] = [];
  public loadError = false;
  public isLoading = false;
  public dataReady = false;

  public page = 1;
  public pageSize = 20;
  public totalPages = 1;
  public totalItems = 0;
  public searchTerm = '';



  public showRolesModal = false;
  public selectedUserForRoles: UserWithRoles | null = null;
  public isDeleting = false;
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
  public isAdmin = false;
  public hasManageUsersPermission = false;
  public hasViewResultsPermission = false;

  private destroy$ = new Subject<void>();
  private searchSubject$ = new Subject<string>();

  constructor(
    private userService: UserManagementService,
    private dialogService: TuiDialogService,
    private cdr: ChangeDetectorRef,
    private alerts: TuiAlertService,
    private router: Router,
    public authService: AuthService
  ) {}

  ngOnInit(): void {
    this.generateRandomColor();
    this.loadUsers();
    this.checkAdminStatus();
    this.setupSearchDebounce();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private setupSearchDebounce(): void {
    this.searchSubject$
      .pipe(
        debounceTime(300),
        distinctUntilChanged(),
        takeUntil(this.destroy$)
      )
      .subscribe(searchTerm => {
        this.searchTerm = searchTerm;
        this.page = 1;
        this.loadUsers();
      });
  }

  checkAdminStatus(): void {
    this.userService.isAdmin().subscribe(isAdmin => {
      this.isAdmin = isAdmin;
      this.cdr.markForCheck();
    });

    this.authService.hasPermission('MANAGE_USERS').subscribe(hasPermission => {
      this.hasManageUsersPermission = hasPermission;
      this.cdr.markForCheck();
    });

    this.authService.hasPermission('VIEW_RESULTS').subscribe(hasPermission => {
      this.hasViewResultsPermission = hasPermission;
      this.cdr.markForCheck();
    });
  }



  loadUsers(): void {
    this.isLoading = true;
    this.loadError = false;
    this.dataReady = false;

    this.userService
      .getUsers(this.page, this.pageSize, this.searchTerm, 'email')
      .pipe(
        takeUntil(this.destroy$),
        catchError(err => {
          this.loadError = true;
          this.isLoading = false;
          this.cdr.markForCheck();
          return of({ data: [], pagination: { page: 1, limit: 20, total: 0, totalPages: 1 } });
        })
      )
      .subscribe({
        next: (response: any) => {
          const users = response.data || [];
          const pagination = response.pagination || { page: 1, limit: 20, total: 0, totalPages: 1 };

          this.page = pagination.page;
          this.totalPages = pagination.totalPages;
          this.totalItems = pagination.total;

          this.rows = users.map((u: any) => {
            const joinDaysAgo = (u.id % 365) + 1;
            const groupNames = Array.isArray(u.groups) && u.groups.length > 0
              ? u.groups.map((g: any) => g.name).join(', ')
              : '—';

            return {
              id: u.id,
              selected: false,
              avatar: u.name ?? u.email,
              firstName: u.firstName ?? '—',
              lastName: u.lastName ?? '—',
              name: `${u.firstName ?? ''} ${u.lastName ?? ''}`.trim(),
              email: u.email ?? '—',
              active: u.isActive,
              organization: u.companyName ?? u.company?.name ?? '—',
              group: groupNames,
              rights: Array.isArray(u.userPermissions) ? u.userPermissions : [],
              roles: u.roles ?? ['ROLE_USER'],
              permissions: Array.isArray(u.userPermissions) ? u.userPermissions : [],
              joinedAt: new Date(Date.now() - joinDaysAgo * 24 * 60 * 60 * 1000).toLocaleDateString('fr-FR')
            };
          });

          this.dataReady = true;

          this.isLoading = false;
          this.cdr.markForCheck();
        },
        error: (error) => {
          console.error('Erreur dans le subscribe:', error);
          this.loadError = true;
          this.isLoading = false;
          this.cdr.markForCheck();
        }
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

  trackByUserId(index: number, user: UserRow): number {
    return user.id;
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

    this.userService.anonymizeUser(ids[0])
      .pipe(takeUntil(this.destroy$))
      .subscribe({
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
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (updatedUser) => {
          const displayRowIndex = this.rows.findIndex(r => r.id === changes.userId);
          if (displayRowIndex !== -1) {
            this.rows[displayRowIndex].roles = changes.roles;
            this.rows[displayRowIndex].permissions = changes.permissions;
            this.rows[displayRowIndex].rights = changes.permissions;
          }

          this.cdr.markForCheck();

          this.closeRolesModal();

          this.authService.getCurrentUser().subscribe({
            next: (currentUser) => {
              if (currentUser && currentUser.id === changes.userId) {
                this.authService.logout();
              }
            }
          });
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

  onPageChange(newPage: number): void {
    if (newPage >= 1 && newPage <= this.totalPages && newPage !== this.page) {
      this.page = newPage;
      this.loadUsers();
    }
  }

  onPageSizeChange(newSize: number): void {
    this.pageSize = newSize;
    this.page = 1;
    this.loadUsers();
  }

  onSearchChange(searchTerm: string): void {
    this.searchSubject$.next(searchTerm);
  }

  get hasNextPage(): boolean {
    return this.page < this.totalPages;
  }

  get hasPrevPage(): boolean {
    return this.page > 1;
  }

  get startItem(): number {
    return (this.page - 1) * this.pageSize + 1;
  }

  get endItem(): number {
    return Math.min(this.page * this.pageSize, this.totalItems);
  }
}
