import { Component, OnInit, OnDestroy, ChangeDetectionStrategy, ChangeDetectorRef, HostListener, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { TuiTable } from '@taiga-ui/addon-table';
import { TuiButton, TuiGroup, TuiDataList, TuiDialogService, TuiAlertService, TuiHintDirective, TuiSizeS } from '@taiga-ui/core';
import { TuiAvatar, TuiCheckbox, TuiChip } from '@taiga-ui/kit';
import { TuiCell } from '@taiga-ui/layout';
import { CompanyService } from '../../services/company.service';
import { PaginationComponent } from '../../components/pagination/pagination.component';
import { FileDownloadService } from '../../services/file-download.service';
import { Subject, takeUntil, forkJoin } from 'rxjs';
import { RouterModule } from '@angular/router';
import { AddCompanyModalComponent } from '../../components/add-company-modal/add-company-modal.component';

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
  activeUsers: number;
  createdAt: string;
  lastActivity: string;
  users?: any[];
}

@Component({
  selector: 'app-company-management',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    TuiTable,
    TuiButton,
    TuiCheckbox,
    TuiChip,
    TuiAvatar,
    TuiCell,
    TuiHintDirective,
    PaginationComponent,
    RouterModule,
    AddCompanyModalComponent,
  ],
  providers: [],
  templateUrl: './company-management.component.html',
  styleUrls: ['./company-management.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CompanyManagementComponent implements OnInit, OnDestroy {
  public rows: CompanyRow[] = [];
  public filterStatus = '';
  public filterKeyword = '';
  public statusOptions: string[] = ['Actif', 'Inactif'];

  public size: TuiSizeS = 's';

  public page = 1;
  public pageSize = 10;
  public sortColumn: keyof CompanyRow | '' = '';
  public sortDirection: 'asc' | 'desc' = 'asc';
  public open = false;
  public items = ['Ajouter une entreprise', 'Exporter CSV', 'Exporter JSON', 'Importer CSV'];

  public highlightColor: string = '';

  public loadError = false;

  public showAddCompanyModal = false;
  public showImportModal = false;
  public selectedFile: File | null = null;
  public isDeleting = false; // Protection contre les appels multiples

  private readonly MAX_VISIBLE_GROUPS = 2;
  private destroy$ = new Subject<void>();

  constructor(
    private companyService: CompanyService,
    private dialogService: TuiDialogService,
    private cdr: ChangeDetectorRef,
    private alerts: TuiAlertService,
    private router: Router,
    private elementRef: ElementRef,
    private fileDownloadService: FileDownloadService
  ) {}

  ngOnInit(): void {
    this.generateRandomColor();
    this.loadCompanies();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: Event): void {
    const target = event.target as any;
    if (target && typeof target.closest === 'function' && !target.closest('.custom-dropdown')) {
      this.open = false;
    }
  }

  private generateRandomColor(): void {
    const colors = [
      '#257D54', '#FAA24B', '#D30D4C',
    ];

    const index = Math.floor(Math.random() * colors.length);
    this.highlightColor = colors[index];
  }

  onDropdownAction(action: string): void {
    switch (action) {
      case 'Ajouter une entreprise':
        this.showAddCompanyModal = true;
        break;
      case 'Exporter CSV':
        this.exportCsv();
        break;
      case 'Exporter JSON':
        this.exportJson();
        break;
      case 'Importer CSV':
        this.showImportModal = true;
        break;
    }
    this.open = false;
  }

  toggleDropdown(): void {
    this.open = !this.open;
  }

  private exportCsv(): void {
    this.companyService.exportCompaniesCsv()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (csvContent: string) => {
          const filename = `entreprises_${new Date().toISOString().split('T')[0]}.csv`;
          this.fileDownloadService.downloadCsv(csvContent, filename);
          this.alerts.open('Export CSV réussi !', { appearance: 'success' }).subscribe();
        },
        error: (error: any) => {
          console.error('Erreur export CSV:', error);
          this.alerts.open('Erreur lors de l\'export CSV', { appearance: 'error' }).subscribe();
        }
      });
  }

  private exportJson(): void {
    this.companyService.exportCompaniesJson()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (result: any) => {
          const filename = `entreprises_${new Date().toISOString().split('T')[0]}.json`;
          this.fileDownloadService.downloadJson(result.data, filename);
          this.alerts.open('Export JSON réussi !', { appearance: 'success' }).subscribe();
        },
        error: (error: any) => {
          console.error('Erreur export JSON:', error);
          this.alerts.open('Erreur lors de l\'export JSON', { appearance: 'error' }).subscribe();
        }
      });
  }



  onCompanyCreated(company: any): void {
    this.showAddCompanyModal = false;
    this.loadCompanies();
  }

  onAddCompanyCancelled(): void {
    this.showAddCompanyModal = false;
  }

  onImportCancelled(): void {
    this.showImportModal = false;
  }

  onFileSelected(event: any): void {
    const file = event.target.files[0];
    if (file && file.type === 'text/csv') {
      this.selectedFile = file;
    } else {
      this.alerts.open('Veuillez sélectionner un fichier CSV valide', { appearance: 'warning' }).subscribe();
      this.selectedFile = null;
    }
  }

  importCsvFile(): void {
    if (!this.selectedFile) return;

    this.companyService.importCompaniesCsv(this.selectedFile)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
      next: (result: any) => {
        this.alerts.open(
          `Import terminé ! ${result.success} entreprises créées${result.errors.length > 0 ? `, ${result.errors.length} erreurs` : ''}`,
          { appearance: 'success' }
        ).subscribe();

        if (result.success > 0) {
          this.loadCompanies();
        }

        this.showImportModal = false;
        this.selectedFile = null;
      },
      error: (error: any) => {
        console.error('Erreur import CSV:', error);
        this.alerts.open('Erreur lors de l\'import CSV', { appearance: 'error' }).subscribe();
      }
    });
  }

  private loadCompanies(): void {
    this.companyService
      .getCompanies()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          // Vérifier le format de réponse
          const companies = response.data || response;
          this.rows = companies.map((c: any) => {
            const userCount = c.users?.length ?? 0;
            const groups: Group[] = c.groups ?? [];
            const groupName = groups.length ? groups[0].name : null;

            return {
              id: c.id,
              selected: false,
              name: c.name ?? '—',
              userCount: userCount,
              groups: groups,
              users: c.users ?? [],
              activeUsers: Math.floor(userCount * (0.6 + Math.random() * 0.35)),
              createdAt: new Date(Date.now() - (Math.floor(Math.random() * 1000) + 30) * 24 * 60 * 60 * 1000).toLocaleDateString('fr-FR'),
              lastActivity: new Date(Date.now() - Math.floor(Math.random() * 7) * 24 * 60 * 60 * 1000).toLocaleDateString('fr-FR'),
            };
          });

          this.applyFilters();
        },
        error: (error: any) => {
          this.loadError = true;
          this.cdr.markForCheck();
        }
      });
  }

  applyFilters(): void {
    let filtered = this.rows;

    if (this.filterStatus) {
      filtered = filtered.filter(r => r.groups.some(g => g.name === this.filterStatus));
    }

    if (this.filterKeyword) {
      const kw = this.filterKeyword.toLowerCase();
      filtered = filtered.filter(r =>
        r.name.toLowerCase().includes(kw) ||
        r.groups.some(g => g.name.toLowerCase().includes(kw))
      );
    }

    this.rows = filtered;
    this.applySort();
    this.page = 1;
    this.cdr.markForCheck();
  }

  applySort(): void {
    if (!this.sortColumn) return;

    this.rows.sort((a, b) => {
      if (this.sortColumn === 'name') {
        if (a.name < b.name) return this.sortDirection === 'asc' ? -1 : 1;
        if (a.name > b.name) return this.sortDirection === 'asc' ? 1 : -1;
      }
      if (this.sortColumn === 'userCount') {
        if (a.userCount < b.userCount) return this.sortDirection === 'asc' ? -1 : 1;
        if (a.userCount > b.userCount) return this.sortDirection === 'asc' ? 1 : -1;
      }
      return 0;
    });
  }

  sortBy(column: keyof CompanyRow): void {
    if (this.sortColumn === column) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortColumn = column;
      this.sortDirection = 'asc';
    }
    this.applySort();
  }

  onPageChange(newPage: number): void {
    this.page = newPage;
    this.cdr.markForCheck();
  }

  get pagedRows(): CompanyRow[] {
    const start = (this.page - 1) * this.pageSize;
    const end = start + this.pageSize;
    return this.rows.slice(start, end);
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

  onClick(): void {
    this.open = false;
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

    const confirmed = window.confirm(`Supprimer ${count} entreprise${count > 1 ? 's' : ''} ?`);

    if (!confirmed) {
      return;
    }

    this.isDeleting = true;

    const deleteObservables = ids.map(id =>
      this.companyService.deleteCompany(id)
    );

    forkJoin(deleteObservables)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
      next: (results) => {
        this.isDeleting = false;
        this.loadCompanies();
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
      error: (error) => {
        this.isDeleting = false;

        this.alerts.open('Échec de la suppression', {
          label: 'Erreur',
          appearance: 'danger',
          autoClose: 3000,
        }).subscribe();
      },
    });
  }

  getVisibleGroups(groups: Group[]): Group[] {
    return groups.slice(0, this.MAX_VISIBLE_GROUPS);
  }

  getRemainingGroupsCount(groups: Group[]): number {
    return Math.max(0, groups.length - this.MAX_VISIBLE_GROUPS);
  }

  getGroupsTooltip(groups: Group[]): string {
    if (!groups || groups.length <= this.MAX_VISIBLE_GROUPS) return '';
    const remainingGroups = groups.slice(this.MAX_VISIBLE_GROUPS);
    return remainingGroups.map(g => g.name).join(', ');
  }


}
