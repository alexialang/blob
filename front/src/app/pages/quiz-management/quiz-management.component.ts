import {
  Component,
  OnInit,
  OnDestroy,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { TuiTable } from '@taiga-ui/addon-table';
import {
  TuiButton,
  TuiDialogService,
  TuiAlertService,
  TuiHintDirective,
  TuiIcon,
} from '@taiga-ui/core';
import { TuiAvatar, TuiCheckbox, TuiChip } from '@taiga-ui/kit';
import { TuiCell } from '@taiga-ui/layout';
import { QuizManagementService } from '../../services/quiz-management.service';
import { FileDownloadService } from '../../services/file-download.service';
import {
  forkJoin,
  Subject,
  takeUntil,
  debounceTime,
  distinctUntilChanged,
  catchError,
  of,
} from 'rxjs';
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
  createdAt?: string;
  questionsCount?: number;
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
    TuiAvatar,
    TuiCell,
    TuiHintDirective,
    TuiIcon,
    PaginationComponent,
  ],
  providers: [],
  templateUrl: './quiz-management.component.html',
  styleUrls: ['./quiz-management.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class QuizManagementComponent implements OnInit, OnDestroy {
  public rows: QuizRow[] = [];
  public loadError = false;
  public isLoading = false;
  public dataReady = false;

  public page = 1;
  public pageSize = 20;
  public totalPages = 1;
  public totalItems = 0;
  public searchTerm = '';

  public size: TuiSizeS = 's';
  public open = false;

  highlightColor: string = '';

  public isDeleting = false;
  public showImportModal = false;
  public selectedFile: File | null = null;

  private readonly MAX_VISIBLE_GROUPS = 2;
  private destroy$ = new Subject<void>();
  private searchSubject$ = new Subject<string>();

  constructor(
    private quizService: QuizManagementService,
    private dialogService: TuiDialogService,
    private cdr: ChangeDetectorRef,
    private alerts: TuiAlertService,
    private router: Router,
    private fileDownloadService: FileDownloadService
  ) {}

  ngOnInit(): void {
    this.generateRandomColor();
    this.loadQuizzes();
    this.setupSearchDebounce();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private setupSearchDebounce(): void {
    this.searchSubject$
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntil(this.destroy$))
      .subscribe(searchTerm => {
        this.searchTerm = searchTerm;
        this.page = 1;
        this.loadQuizzes();
      });
  }

  private generateRandomColor(): void {
    const colors = ['#257D54', '#FAA24B', '#D30D4C'];

    const index = Math.floor(Math.random() * colors.length);
    this.highlightColor = colors[index];
  }

  getQuizStats(row: any): string {
    const totalAttempts = row.userAnswers?.length || 0;
    const uniquePlayers = row.userAnswers
      ? new Set(row.userAnswers.map((answer: any) => answer.userId || answer.user?.id)).size
      : 0;

    if (totalAttempts > 0 && uniquePlayers > 0) {
      return `${uniquePlayers} joueurs • ${totalAttempts} parties`;
    } else if (totalAttempts > 0) {
      return `${totalAttempts} parties`;
    } else if (row.isPublic) {
      return 'Quiz public - 0 joueur';
    } else {
      return 'Quiz privé - 0 joueur';
    }
  }

  loadQuizzes(): void {
    this.isLoading = true;
    this.loadError = false;
    this.dataReady = false;

    this.quizService
      .getQuizzes(this.page, this.pageSize, this.searchTerm, 'title')
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
          const quizzes = response.data || [];
          const pagination = response.pagination || { page: 1, limit: 20, total: 0, totalPages: 1 };

          this.page = pagination.page;
          this.totalPages = pagination.totalPages;
          this.totalItems = pagination.total;

          this.rows = quizzes.map((quiz: any) => {
            const createdDaysAgo = Math.floor(Math.random() * 180) + 1;
            const questionsCount = quiz.questionCount || Math.floor(Math.random() * 15) + 5;

            const createdBy = quiz.user 
              ? `${quiz.user.firstName || ''} ${quiz.user.lastName || ''}`.trim() || quiz.user.email || 'Utilisateur inconnu'
              : 'Créateur inconnu';


            return {
              id: quiz.id,
              selected: false,
              title: quiz.title,
              description: quiz.description,
              createdBy,
              category: quiz.category?.name ?? null,
              isPublic: quiz.isPublic ?? false,
              status: quiz.status ?? 'draft',
              groups: quiz.groups || [],
              stats: `${questionsCount} questions • Créé il y a ${createdDaysAgo}j`,
              createdAt: quiz.dateCreation
                ? new Date(quiz.dateCreation).toLocaleDateString('fr-FR')
                : 'N/A',
              questionsCount: questionsCount,
            };
          });

          this.dataReady = true;
          this.isLoading = false;
          this.cdr.markForCheck();
        },
        error: error => {
          console.error('Erreur dans le subscribe:', error);
          this.loadError = true;
          this.isLoading = false;
          this.cdr.markForCheck();
        },
      });
  }

  onPageChange(newPage: number): void {
    if (newPage >= 1 && newPage <= this.totalPages && newPage !== this.page) {
      this.page = newPage;
      this.loadQuizzes();
    }
  }

  onPageSizeChange(newSize: number): void {
    this.pageSize = newSize;
    this.page = 1;
    this.loadQuizzes();
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

  get hasSelection(): boolean {
    return this.rows.some(r => r.selected);
  }

  get allSelected(): boolean {
    return this.rows.length > 0 && this.rows.every(r => r.selected);
  }

  toggleAll(checked: boolean): void {
    this.rows.forEach(r => (r.selected = checked));
    this.cdr.markForCheck();
  }

  onQuizSelectionChange(): void {
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
    if (this.isDeleting) {
      return;
    }

    const count = ids.length;

    const confirmed = window.confirm(`Supprimer ${count} quiz${count > 1 ? 's' : ''} ?`);

    if (!confirmed) {
      return;
    }

    this.isDeleting = true;

    const deletes$ = ids.map(id => this.quizService.deleteQuiz(id));
    forkJoin(deletes$).subscribe({
      next: results => {
        this.isDeleting = false;

        this.loadQuizzes();

        this.rows.forEach(r => (r.selected = false));
        this.cdr.markForCheck();

        this.alerts
          .open(`${count} quiz${count > 1 ? 's' : ''} supprimé${count > 1 ? 's' : ''}`, {
            label: 'Succès',
            appearance: 'positive',
            autoClose: 3000,
          })
          .subscribe();
      },
      error: error => {
        this.isDeleting = false;
      },
    });
  }

  exportQuizzes(): void {
    const selectedQuizzes = this.rows.filter(quiz => quiz.selected);
    const hasSelection = selectedQuizzes.length > 0;

    if (hasSelection) {
      this.exportSelectedQuizzes(selectedQuizzes);
    } else {
      this.exportAllQuizzes();
    }
  }

  private exportSelectedQuizzes(selectedQuizzes: QuizRow[]): void {
    const exportData = {
      exportDate: new Date().toISOString(),
      exportType: 'selective',
      totalQuizzes: selectedQuizzes.length,
      quizzes: selectedQuizzes,
    };

    const filename = `quiz_selection_${selectedQuizzes.length}_${new Date().toISOString().split('T')[0]}.json`;
    this.fileDownloadService.downloadJson(exportData, filename);

    const message =
      selectedQuizzes.length === 1
        ? `Export réussi ! 1 quiz sélectionné exporté`
        : `Export réussi ! ${selectedQuizzes.length} quiz sélectionnés exportés`;

    this.alerts.open(message, { appearance: 'success' }).subscribe();

    selectedQuizzes.forEach(quiz => (quiz.selected = false));
    this.cdr.markForCheck();
  }

  private exportAllQuizzes(): void {
    this.quizService
      .getQuizzes()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (quizzes: any[]) => {
          const exportData = {
            exportDate: new Date().toISOString(),
            exportType: 'complete',
            totalQuizzes: quizzes.length,
            quizzes: quizzes,
          };
          const filename = `quiz_export_complet_${new Date().toISOString().split('T')[0]}.json`;
          this.fileDownloadService.downloadJson(exportData, filename);
          this.alerts.open('Export JSON complet réussi !', { appearance: 'success' }).subscribe();
        },
        error: (_error: any) => {
          console.error('Erreur export JSON:', _error);
          this.alerts.open("Erreur lors de l'export JSON", { appearance: 'error' }).subscribe();
        },
      });
  }

  showImportDialog(): void {
    this.showImportModal = true;
  }

  onImportCancelled(): void {
    this.showImportModal = false;
    this.selectedFile = null;
  }

  onFileSelected(event: any): void {
    const file = event.target.files[0];
    if (file && file.type === 'application/json') {
      this.selectedFile = file;
    } else {
      this.alerts
        .open('Veuillez sélectionner un fichier JSON valide', { appearance: 'warning' })
        .subscribe();
      this.selectedFile = null;
    }
  }

  importQuizFile(): void {
    if (!this.selectedFile) return;

    const reader = new FileReader();
    reader.onload = e => {
      try {
        const importData = JSON.parse(e.target?.result as string);

        if (!importData.quizzes || !Array.isArray(importData.quizzes)) {
          throw new Error('Format de fichier invalide');
        }

        this.alerts
          .open(
            `Import simulé réussi ! ${importData.quizzes.length} quiz détectés dans le fichier`,
            { appearance: 'info' }
          )
          .subscribe();

        this.showImportModal = false;
        this.selectedFile = null;
      } catch (error: any) {
        console.error('Erreur parsing JSON:', error);
        this.alerts
          .open('Erreur : Format de fichier JSON invalide', { appearance: 'error' })
          .subscribe();
      }
    };

    reader.onerror = () => {
      this.alerts.open('Erreur lors de la lecture du fichier', { appearance: 'error' }).subscribe();
    };

    reader.readAsText(this.selectedFile);
  }

  onCreateQuiz(): void {
    this.router.navigate(['/creation-quiz']);
  }

  editQuiz(id: number): void {
    if (!id) {
      this.router.navigate(['/creation-quiz']);
      return;
    }

    this.router.navigate(['/creation-quiz', id]);
  }

  getVisibleGroups(groups: Group[]): Group[] {
    return groups.slice(0, this.MAX_VISIBLE_GROUPS);
  }

  getRemainingGroupsCount(groups: Group[]): number {
    return Math.max(0, groups.length - this.MAX_VISIBLE_GROUPS);
  }

  getGroupsTooltip(groups: Group[]): string {
    return groups
      .slice(this.MAX_VISIBLE_GROUPS)
      .map(g => g.name)
      .join(', ');
  }

  getExportButtonText(): string {
    const selectedCount = this.rows.filter(quiz => quiz.selected).length;
    if (selectedCount === 0) {
      return 'Exporter tout';
    } else if (selectedCount === 1) {
      return 'Exporter (1)';
    } else {
      return `Exporter (${selectedCount})`;
    }
  }

  getExportTooltip(): string {
    const selectedCount = this.rows.filter(quiz => quiz.selected).length;
    if (selectedCount === 0) {
      return 'Exporter tous les quiz au format JSON';
    } else if (selectedCount === 1) {
      return 'Exporter le quiz sélectionné au format JSON';
    } else {
      return `Exporter les ${selectedCount} quiz sélectionnés au format JSON`;
    }
  }
}
