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
  TuiChip,
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
  TuiIcon,
  TuiHintDirective,
} from '@taiga-ui/core';
import { TUI_CONFIRM } from '@taiga-ui/kit';
import { catchError, of, forkJoin } from 'rxjs';
import { PaginationComponent } from '../../components/pagination/pagination.component';
import { CompanyManagementService } from '../../services/company-management.service';
import {RouterLink} from '@angular/router';

type TuiSizeS = 's' | 'm';

interface Group {
  id: number;
  name: string;
}

interface CompanyRow {
  id: number;
  selected: boolean;
  name: string;
  userCount: number;
  groups: Group[];
}

@Component({
  selector: 'app-company-management',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    TuiTable,
    TuiCell,
    TuiAvatar,
    TuiCheckbox,
    TuiChip,
    TuiButton,
    TuiDataList,
    TuiGroup,
    TuiDropdown,
    TuiChevron,
    TuiIcon,
    PaginationComponent,
    TuiHintDirective,
    RouterLink,
  ],
  providers: [
    { provide: TUI_CONFIRM, useValue: TUI_CONFIRM },
  ],
  templateUrl: './company-management.component.html',
  styleUrls: ['./company-management.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CompanyManagementComponent implements OnInit {
  readonly sizes: TuiSizeS[] = ['m', 's'];
  size: TuiSizeS = 'm';

  private allRows: {
    id: number;
    selected: boolean;
    name: string;
    collaboratorCount: number;
    groupName: string | null;
    groups: Group[];
  }[] = [];

  public rows: CompanyRow[] = [];
  public loadError = false;
  public page = 1;
  public pageSize = 20;
  public filterStatus = '';
  public filterKeyword = '';
  public statusOptions: string[] = [];
  public sortColumn: keyof CompanyRow | '' = '';
  public sortDirection: 'asc' | 'desc' = 'asc';
  public open = false;
  public items = ['Ajouter une entreprise', 'Exporter la liste'];
  private readonly MAX_VISIBLE_GROUPS = 2;

  constructor(
    private readonly companyService: CompanyManagementService,
    private readonly dialogService: TuiDialogService,
    private readonly cdr: ChangeDetectorRef,
    private readonly alerts: TuiAlertService
  ) {}

  ngOnInit(): void {
    this.loadCompanies();
  }

  private loadCompanies(): void {
    this.companyService
      .getCompanies()
      .pipe(
        catchError(err => {
          console.error('Erreur getCompanies()', err);
          this.loadError = true;
          return of([]);
        })
      )
      .subscribe((companies: any[]) => {
        this.allRows = companies.map(c => {
          const userCount = c.users?.length ?? 0;
          const groups: Group[] = c.groups ?? [];
          const groupName = groups.length ? groups[0].name : null;

          return {
            id: c.id,
            selected: false,
            name: c.name ?? '—',
            collaboratorCount: userCount,
            groupName: groupName,
            groups: groups,
          };
        });

        const uniqueGroups = [...new Set(
          this.allRows
            .map(r => r.groupName)
            .filter(g => g !== null)
        )];
        this.statusOptions = uniqueGroups;

        this.applyFilters();
      });
  }

  applyFilters(): void {
    let filtered = this.allRows;

    if (this.filterStatus) {
      filtered = filtered.filter(r => r.groupName === this.filterStatus);
    }

    if (this.filterKeyword) {
      const kw = this.filterKeyword.toLowerCase();
      filtered = filtered.filter(r =>
        r.name.toLowerCase().includes(kw) ||
        (r.groupName?.toLowerCase().includes(kw) ?? false)
      );
    }

    this.rows = filtered.map(r => ({
      id: r.id,
      selected: r.selected,
      name: r.name,
      userCount: r.collaboratorCount,
      groups: r.groups,
    }));

    this.applySort();
    this.page = 1;
    this.cdr.markForCheck();
  }

  sortBy(column: keyof CompanyRow): void {
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
      const aVal = a[col];
      const bVal = b[col];

      if (typeof aVal === 'string' && typeof bVal === 'string') {
        return dir === 'asc'
          ? aVal.localeCompare(bVal)
          : bVal.localeCompare(aVal);
      }

      if (typeof aVal === 'number' && typeof bVal === 'number') {
        return dir === 'asc' ? aVal - bVal : bVal - aVal;
      }

      return 0;
    });

    this.cdr.markForCheck();
  }

  get pagedRows(): CompanyRow[] {
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
            content: `Supprimer ${count} entreprise${count > 1 ? 's' : ''} ?`,
            yes: 'Supprimer',
            no: 'Annuler',
          },
        }
      )
      .subscribe(confirmed => {
        if (!confirmed) return;

        const deleteObservables = ids.map(id =>
          this.companyService.deleteCompany(id)
        );

        forkJoin(deleteObservables).subscribe({
          next: () => {
            this.allRows = this.allRows.filter(r => !ids.includes(r.id));
            this.applyFilters();
            this.rows.forEach(r => r.selected = false);
            this.cdr.markForCheck();

            this.alerts.open(
              `${count} entreprise${count > 1 ? 's' : ''} supprimée${count > 1 ? 's' : ''}`,
              {
                label: 'Succès',
                appearance: 'positive',
                autoClose: 3000,
              }
            ).subscribe();
          },
          error: () => {
            this.alerts.open('Échec de la suppression', {
              label: 'Erreur',
              appearance: 'danger',
              autoClose: 3000,
            }).subscribe();
          },
        });
      });
  }

  onClick(): void {
    this.open = false;
  }

  getVisibleGroups(groups: Group[]): Group[] {
    return groups?.slice(0, this.MAX_VISIBLE_GROUPS) ?? [];
  }

  getRemainingGroupsCount(groups: Group[]): number {
    return Math.max(0, (groups?.length ?? 0) - this.MAX_VISIBLE_GROUPS);
  }

  getGroupsTooltip(groups: Group[]): string {
    if (!groups || groups.length <= this.MAX_VISIBLE_GROUPS) return '';
    const remainingGroups = groups.slice(this.MAX_VISIBLE_GROUPS);
    return remainingGroups.map(g => g.name).join(', ');
  }
}
