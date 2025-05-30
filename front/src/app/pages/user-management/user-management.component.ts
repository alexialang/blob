import {
  Component,
  OnInit,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

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
import { TUI_CONFIRM } from '@taiga-ui/kit';

import { catchError, of } from 'rxjs';

import { UserManagementService } from '../../services/user-management.service';
import { PaginationComponent } from '../../components/pagination/pagination.component';

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
  stats: number;
  rights: string[];
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
  ],
  providers: [
    { provide: TUI_CONFIRM, useValue: TUI_CONFIRM },
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

  constructor(
    private userService: UserManagementService,
    private dialogService: TuiDialogService,
    private cdr: ChangeDetectorRef,
    private alerts: TuiAlertService
  ) {}

  ngOnInit(): void {
    this.userService
      .getUsers()
      .pipe(
        catchError(err => {
          console.error(' Erreur getUsers()', err);
          this.loadError = true;
          return of([]);
        })
      )
      .subscribe((users: any[]) => {
        this.allRows = users.map(u => ({
          id: u.id,
          selected: false,
          avatar: u.name ?? u.email,
          firstName: u.firstName ?? '—',
          lastName: u.lastName ?? '—',
          name: `${u.firstName ?? ''} ${u.lastName ?? ''}`.trim(),
          email: u.email ?? '—',
          active: u.isActive,
          organization: u.company?.name ?? '—',
          group:
            Array.isArray(u.company?.groups) && u.company.groups.length
              ? u.company.groups.map((g: any) => g.name).join(', ')
              : '—',
          stats: Math.floor(Math.random() * 1000),
          rights: (u.userPermissions ?? []).map((p: any) => p.permission),
        }));
        console.log(users)
        this.orgOptions = Array.from(new Set(this.allRows.map(r => r.organization))).sort();
        this.rightsOptions = Array.from(new Set(this.allRows.flatMap(r => r.rights))).sort();
        this.applyFilters();
      });
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
    const count = ids.length;
    this.dialogService
      .open<boolean>(
        TUI_CONFIRM,
        {
          label: 'Confirmation',
          data: {
            content: `Supprimer ${count} utilisateur${count > 1 ? 's' : ''} ?`,
            yes: 'Supprimer',
            no: 'Annuler',
          },
        }
      )
      .subscribe(confirmed => {
        if (!confirmed) return;
        this.userService.softDeleteUser(ids[0]).subscribe({
          next: () => {
            this.allRows = this.allRows.filter(r => !ids.includes(r.id));
            this.applyFilters();
            this.rows.forEach(r => r.selected = false);
            this.cdr.markForCheck();
            this.alerts.open('Suppression terminée', {
              label: 'Info',
              appearance: 'positive',
              autoClose: 2000,
            }).subscribe();
          },
          error: err => {
            console.error('Suppression échouée', err);
            this.alerts.open('Échec de la suppression', {
              label: 'Erreur',
              appearance: 'danger',
              autoClose: 2000,
            }).subscribe();
          },
        });
      });
  }

  onClick(): void {
    this.open = false;
  }
}
