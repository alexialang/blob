import {
  Component,
  OnInit,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { TuiTable, TuiSortChange } from '@taiga-ui/addon-table';
import { TuiCell } from '@taiga-ui/layout';
import {
  TuiAvatar,
  TuiBadge,
  TuiCheckbox,
  TuiChevron,
  TuiDataListWrapper,
  TuiItemsWithMore,
  TuiStatus,
} from '@taiga-ui/kit';
import {
  TuiButton,
  TuiDataList,
  TuiDropdown,
  TuiIcon,
  TuiLink,
  TuiSpinButton,
  TuiTextfield,
  TuiTitle,
} from '@taiga-ui/core';
import { TuiActiveZone, TuiObscured } from '@taiga-ui/cdk';

import { catchError, of } from 'rxjs';
import { UserManagementService } from '../../services/user-management.service';
import { PaginationComponent } from '../../components/pagination/pagination.component';

type TuiSizeS = 's' | 'm';

interface UserRow {
  id: number;
  selected: boolean;
  avatar: string;
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
    TuiStatus,
    TuiButton,
    TuiDropdown,
    TuiLink,
    TuiTitle,
    PaginationComponent,
    TuiIcon,
    TuiSpinButton,
    TuiChevron,
    TuiObscured,
    TuiActiveZone,
    TuiDataList,
    TuiDataListWrapper,
    TuiTextfield,
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

  protected open = false;
  protected readonly items = ['Ajouter une entreprise', 'Attribuer une entreprise'];
  protected selected = null;

  constructor(
    private userService: UserManagementService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.userService
      .getUsers()
      .pipe(
        catchError((err) => {
          console.error('Erreur getUsers()', err);
          this.loadError = true;
          return of([]);
        })
      )
      .subscribe((users: any[]) => {
        this.allRows = users.map((u) => ({
          id: u.id,
          selected: false,
          avatar: u.name ?? u.email,
          name: u.name ?? '—',
          email: u.email ?? '—',
          active: !u.is_admin,
          organization: u.company?.name ?? '—',
          group: Array.isArray(u.groups) && u.groups.length > 0 ? u.groups[0].name : '—',
          stats: Math.floor(Math.random() * 1000),
          rights: (u.userPermissions ?? []).map((p: any) => p.permission),
        }));

        this.orgOptions = Array.from(
          new Set(this.allRows.map((r) => r.organization))
        ).sort();

        this.rightsOptions = Array.from(
          new Set(this.allRows.flatMap((r) => r.rights))
        ).sort();

        this.applyFilters();
      });
  }

  applyFilters(): void {
    let filtered = this.allRows;

    if (this.filterOrg) {
      filtered = filtered.filter((r) => r.organization === this.filterOrg);
    }

    if (this.filterRight) {
      filtered = filtered.filter((r) => r.rights.includes(this.filterRight));
    }

    if (this.filterKeyword) {
      const kw = this.filterKeyword.toLowerCase();
      filtered = filtered.filter(
        (r) =>
          r.name.toLowerCase().includes(kw) ||
          r.email.toLowerCase().includes(kw)
      );
    }

    this.rows = filtered;
    this.applySort();
    this.page = 1;
    this.cdr.markForCheck();
  }

  sortBy(column: keyof UserRow): void {
    if (this.sortColumn === column) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortColumn = column;
      this.sortDirection = 'asc';
    }

    this.applySort();
  }

  onSortChange({ sortKey, sortDirection }: TuiSortChange<UserRow>): void {
    this.sortColumn = sortKey!;
    this.sortDirection = sortDirection === 1 ? 'asc' : 'desc';
    this.applySort();
  }

  private applySort(): void {
    if (!this.sortColumn) return;

    const column = this.sortColumn;
    const direction = this.sortDirection;

    this.rows.sort((a, b) => {
      const aVal = a[column];
      const bVal = b[column];

      if (typeof aVal === 'string' && typeof bVal === 'string') {
        return direction === 'asc'
          ? aVal.localeCompare(bVal)
          : bVal.localeCompare(aVal);
      }

      if (typeof aVal === 'number' && typeof bVal === 'number') {
        return direction === 'asc' ? aVal - bVal : bVal - aVal;
      }

      if (typeof aVal === 'boolean' && typeof bVal === 'boolean') {
        return direction === 'asc'
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
    return this.rows.some((r) => r.selected);
  }

  get allSelected(): boolean {
    return this.rows.length > 0 && this.rows.every((r) => r.selected);
  }

  toggleAll(checked: boolean): void {
    this.rows.forEach((r) => (r.selected = checked));
  }

  deleteSelected(): void {
    const toDelete = this.rows.filter((r) => r.selected).map((r) => r.id);
    console.log('À supprimer :', toDelete);
  }

  protected onClick(): void {
    this.open = false;
  }
}
