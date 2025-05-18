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
  TuiProgressBar,
  TuiRadioList,
  TuiStatus,
  TuiChip,
} from '@taiga-ui/kit';
import {
  TuiButton,
  TuiDropdown,
  TuiIcon,
  TuiLink,
  TuiTitle,
  TuiAutoColorPipe,
  TuiInitialsPipe,
} from '@taiga-ui/core';

import { catchError, of } from 'rxjs';
import { UserManagementService } from '../../services/user-management.service';

type TuiSizeS = 's' | 'm';

interface UserRow {
  id: number;
  selected: boolean;
  avatar: string;
  name: string;
  email: string;
  active: boolean;
  organization: string;
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
    TuiChip,
    TuiProgressBar,
    TuiRadioList,
    TuiStatus,
    TuiButton,
    TuiDropdown,
    TuiIcon,
    TuiLink,
    TuiTitle,
    TuiAutoColorPipe,
    TuiInitialsPipe,
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


  public filterOrg = '';
  public filterRight = '';
  public filterKeyword = '';
  public orgOptions: string[] = [];
  public rightsOptions: string[] = [];

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
}
