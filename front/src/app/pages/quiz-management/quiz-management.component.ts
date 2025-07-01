import {
  Component,
  OnInit,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TuiTable } from '@taiga-ui/addon-table';
import {
  TuiButton,
  TuiDropdown,
  TuiGroup,
  TuiDataList,
  TuiDialogService,
  TuiAlertService,
  TuiIcon,
  TuiHintDirective,
} from '@taiga-ui/core';
import {
  TUI_CONFIRM,
  TuiAvatar,
  TuiCheckbox,
  TuiChip,
} from '@taiga-ui/kit';
import { TuiCell } from '@taiga-ui/layout';
import { QuizManagementService } from '../../services/quiz-management.service';
import { forkJoin } from 'rxjs';
import { PaginationComponent } from '../../components/pagination/pagination.component';

type TuiSizeS = 's' | 'm';

interface Group {
  id: number;
  name: string;
}

interface QuizRow {
  id: number;
  selected: boolean;
  title: string;
  description?: string;
  createdBy: string;
  category?: string;
  groups: Group[];
  stats?: string;
  isPublic: boolean;
  status: string;
}


@Component({
  selector: 'app-quiz-management',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    TuiTable,
    TuiButton,
    TuiCheckbox,
    TuiChip,
    TuiDropdown,
    TuiGroup,
    TuiDataList,
    TuiAvatar,
    TuiCell,
    TuiIcon,
    TuiHintDirective,
    PaginationComponent,
  ],
  providers: [{ provide: TUI_CONFIRM, useValue: TUI_CONFIRM }],
  templateUrl: './quiz-management.component.html',
  styleUrls: ['./quiz-management.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class QuizManagementComponent implements OnInit {
  public rows: QuizRow[] = [];
  public filterStatus = '';
  public filterKeyword = '';
  public statusOptions: string[] = ['Actif', 'Inactif'];

  public size: TuiSizeS = 's';

  public page = 1;
  public pageSize = 10;
  public sortColumn: keyof QuizRow | '' = '';
  public sortDirection: 'asc' | 'desc' = 'asc';
  public open = false;
  public items = ['Créer un quiz', 'Exporter', 'Importer'];
  public loadError = false;

  private readonly MAX_VISIBLE_GROUPS = 2;

  constructor(
    private quizService: QuizManagementService,
    private dialogService: TuiDialogService,
    private cdr: ChangeDetectorRef,
    private alerts: TuiAlertService
  ) {}

  ngOnInit(): void {
    this.loadQuizzes();
  }

  private loadQuizzes(): void {
    this.quizService.getQuizzes().subscribe({
      next: quizzes => {
        console.log(quizzes);
        this.rows = quizzes.map(quiz => ({
          id: quiz.id,
          selected: false,
          title: quiz.title,
          description: quiz.description,
          createdBy: `${quiz.user?.email || ''} ${quiz.user?.lastName || ''}`.trim(),
          category: quiz.category?.name ?? null,
          isPublic: quiz.isPublic?? 'null',
          status: quiz.status?? 'null',
          groups: quiz.groups || [],
          stats: '',
        }));
        this.applyFilters();
      },
      error: () => {
        this.loadError = true;
        this.cdr.markForCheck();
      },
    });
  }

  applyFilters(): void {
    let filtered = [...this.rows];

    if (this.filterKeyword) {
      const keyword = this.filterKeyword.toLowerCase();
      filtered = filtered.filter(q =>
        q.title.toLowerCase().includes(keyword) ||
        q.createdBy.toLowerCase().includes(keyword)
      );
    }

    this.rows = filtered;
    this.applySort();
    this.page = 1;
    this.cdr.markForCheck();
  }

  sortBy(column: keyof QuizRow): void {
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

      return 0;
    });

    this.cdr.markForCheck();
  }

  get pagedRows(): QuizRow[] {
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
    const ids = this.rows.filter(r => r.selected).map(r => r.id);
    if (!ids.length) return;
    this.confirmDelete(ids);
  }

  deleteSingle(id: number): void {
    this.confirmDelete([id]);
  }

  confirmDelete(ids: number[]): void {
    const count = ids.length;
    this.dialogService.open<boolean>(TUI_CONFIRM, {
      label: 'Confirmation',
      data: {
        content: `Supprimer ${count} quiz${count > 1 ? 's' : ''} ?`,
        yes: 'Supprimer',
        no: 'Annuler',
      },
    }).subscribe(confirmed => {
      if (!confirmed) return;

      const deletes$ = ids.map(id => this.quizService.deleteQuiz(id));
      forkJoin(deletes$).subscribe({
        next: () => {
          this.rows = this.rows.filter(r => !ids.includes(r.id));
          this.rows.forEach(r => r.selected = false);
          this.applyFilters();
          this.alerts.open(
            `${count} quiz${count > 1 ? 's' : ''} supprimé${count > 1 ? 's' : ''}`,
            { label: 'Succès', appearance: 'positive', autoClose: 3000 }
          ).subscribe();
        },
        error: () => {
          this.alerts.open('Erreur lors de la suppression.', {
            label: 'Erreur',
            appearance: 'danger',
            autoClose: 3000
          }).subscribe();
        },
      });
    });
  }

  onClick(): void {
    this.open = false;
  }

  getVisibleGroups(groups: Group[]): Group[] {
    return groups.slice(0, this.MAX_VISIBLE_GROUPS);
  }

  getRemainingGroupsCount(groups: Group[]): number {
    return Math.max(0, groups.length - this.MAX_VISIBLE_GROUPS);
  }

  getGroupsTooltip(groups: Group[]): string {
    return groups.slice(this.MAX_VISIBLE_GROUPS).map(g => g.name).join(', ');
  }
}
