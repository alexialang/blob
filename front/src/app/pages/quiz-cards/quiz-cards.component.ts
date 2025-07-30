import { Component, OnInit, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import {CommonModule, NgOptimizedImage} from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { QuizManagementService } from '../../services/quiz-management.service';
import { QuizCardComponent } from '../../components/quiz-card/quiz-card.component';
import { QuizCard } from '../../models/quiz.model';
import { PaginationComponent } from '../../components/pagination/pagination.component';

interface CategoryWithPagination {
  name: string;
  quizzes: QuizCard[];
  currentPage: number;
  itemsPerPage: number;
  pageSize: number;
  totalItems: number;
}

type QuizItem   = { type: 'quiz'; data: QuizCard };
type BlobItem   = { type: 'blob' };
type DisplayItem = QuizItem | BlobItem;

@Component({
  selector: 'app-quiz-cards',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    RouterModule,
    QuizCardComponent,
    PaginationComponent,
    NgOptimizedImage,
  ],
  templateUrl: './quiz-cards.component.html',
  styleUrls: ['./quiz-cards.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class QuizCardsComponent implements OnInit {
  originalPopularQuizzes: QuizCard[] = [];
  popularQuizzes: QuizCard[] = [];
  popularQuizzesWithBlobs: DisplayItem[] = [];
  popularCurrentPage = 1;

  originalMyQuizzes: QuizCard[] = [];
  myQuizzes: QuizCard[] = [];
  myQuizzesWithBlobs: DisplayItem[] = [];
  myQuizzesCurrentPage = 1;

  originalRecentQuizzes: QuizCard[] = [];
  recentQuizzes: QuizCard[] = [];
  recentQuizzesWithBlobs: DisplayItem[] = [];
  recentCurrentPage = 1;

  originalCategories: CategoryWithPagination[] = [];
  categories: CategoryWithPagination[] = [];
  categoriesWithBlobs: { [key: string]: DisplayItem[] } = {};

  readonly pageSize = 12;


  loading = true;
  searchTerm = '';
  selectedCategory = '';
  selectedDifficulty = '';

  private flippedCardsCache = new Map<number, boolean>();
  private blobPositions: Record<string, number | null> = {};

  constructor(
    private quizService: QuizManagementService,
    private cdr: ChangeDetectorRef,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadQuizzes();
  }

  private loadQuizzes(): void {
    this.quizService.getOrganizedQuizzes().subscribe({
      next: data => {
        this.originalPopularQuizzes = this.convertToQuizCards(data.popular ?? []);
        this.popularQuizzes = [...this.originalPopularQuizzes];

        this.originalMyQuizzes = this.convertToQuizCards(data.myQuizzes ?? []);
        this.myQuizzes = [...this.originalMyQuizzes];

        this.originalRecentQuizzes = this.convertToQuizCards(data.recent ?? []);
        this.recentQuizzes = [...this.originalRecentQuizzes];

        this.originalCategories = (data.categories ?? []).map(
          (cat: any): CategoryWithPagination => ({
            name: cat.name,
            quizzes: this.convertToQuizCards(cat.quizzes ?? []),
            currentPage: 1,
            itemsPerPage: this.pageSize,
            pageSize: this.pageSize,
            totalItems: cat.quizzes?.length ?? 0,
          })
        );
        this.categories = this.originalCategories.map(cat => ({ ...cat }));

        this.calculateAllBlobs();

        this.loading = false;
        this.cdr.markForCheck();
      },
      error: err => {
        this.loading = false;
        this.cdr.markForCheck();
      }
    });
  }

  private convertToQuizCards(raw: any[]): QuizCard[] {
    return raw.map(q => {
      const companyName = (q.company?.name ?? q.user?.company?.name) ?? 'Inconnu';
      const groupName   = q.groups?.[0]?.name ?? null;
      const isPublic    = q.is_public ?? q.isPublic ?? false;
      return {
        id: q.id,
        title: q.title,
        description: q.description ?? 'Aucune description disponible',
        is_public: isPublic,
        company: companyName,
        groupName,
        category: q.category?.name ?? 'Catégorie inconnue',
        difficulty: (q.difficultyLabel ?? q.difficulty) ?? 'Niveau non renseigné',
        rating: 0,
        isLiked: false,
        questionCount: q.questionCount,
        isFlipped: this.flippedCardsCache.get(q.id) ?? false,
        playMode: 'solo' as const,
      };
    });
  }

  flipCard(quiz: QuizCard): void {
    quiz.isFlipped = !quiz.isFlipped;
    this.flippedCardsCache.set(quiz.id, quiz.isFlipped);
    this.cdr.markForCheck();
  }

  toggleLike(quiz: QuizCard): void {
    quiz.isLiked = !quiz.isLiked;
    this.cdr.markForCheck();
  }

  togglePlayMode(quiz: QuizCard): void {
    quiz.playMode = quiz.playMode === 'solo' ? 'team' : 'solo';
    this.cdr.markForCheck();
  }

  startQuiz(quiz: QuizCard): void {
    if (quiz.playMode === 'solo') {
      this.router.navigate(['/quiz', quiz.id, 'play'])
        .catch(err => console.error('Navigation erreur', err));
    } else if (quiz.playMode === 'team') {
      this.router.navigate(['/multiplayer/create', quiz.id])
        .catch(err => console.error('Navigation erreur', err));
    }
  }

  protected getSectionPage(arr: QuizCard[], page: number): QuizCard[] {
    const start = (page - 1) * this.pageSize;
    return arr.slice(start, start + this.pageSize);
  }

  onPopularPageChange(page: number): void {
    this.popularCurrentPage = page;
    const key = `popular-${page}`;
    this.popularQuizzesWithBlobs = this.addRandomBlobsLimited(
      this.getSectionPage(this.popularQuizzes, page),
      this.pageSize,
      key
    );
    this.cdr.markForCheck();
  }

  onMyQuizzesPageChange(page: number): void {
    this.myQuizzesCurrentPage = page;
    const key = `my-${page}`;
    this.myQuizzesWithBlobs = this.addRandomBlobsLimited(
      this.getSectionPage(this.myQuizzes, page),
      this.pageSize,
      key
    );
    this.cdr.markForCheck();
  }

  onRecentPageChange(page: number): void {
    this.recentCurrentPage = page;
    const key = `recent-${page}`;
    this.recentQuizzesWithBlobs = this.addRandomBlobsLimited(
      this.getSectionPage(this.recentQuizzes, page),
      this.pageSize,
      key
    );
    this.cdr.markForCheck();
  }

  onCategoryPageChange(cat: CategoryWithPagination, page: number): void {
    cat.currentPage = page;
    this.recalculateCategoryBlobs(cat);
    this.cdr.markForCheck();
  }

  private recalculateCategoryBlobs(cat: CategoryWithPagination): void {
    const key = `${cat.name}-${cat.currentPage}`;
    const start = (cat.currentPage - 1) * cat.itemsPerPage;
    const slice = cat.quizzes.slice(start, start + cat.itemsPerPage);
    this.categoriesWithBlobs[key] = this.addRandomBlobsLimited(slice, this.pageSize, key);
  }

  private calculateAllBlobs(): void {
    this.onPopularPageChange(this.popularCurrentPage);
    this.onMyQuizzesPageChange(this.myQuizzesCurrentPage);
    this.onRecentPageChange(this.recentCurrentPage);
    // Catégories
    this.categories.forEach(cat => this.recalculateCategoryBlobs(cat));
  }

  public addRandomBlobsLimited(
    quizzes: QuizCard[],
    maxItems: number,
    key: string
  ): DisplayItem[] {
    if (!(key in this.blobPositions)) {
      if (quizzes.length >= 1 && Math.random() < 0.25) {
        this.blobPositions[key] = Math.floor(Math.random() * quizzes.length);
      } else {
        this.blobPositions[key] = null;
      }
    }

    const items: DisplayItem[] = quizzes.map(q => ({ type: 'quiz', data: q }));
    const pos = this.blobPositions[key];
    if (pos !== null) {
      items.splice(pos, 0, { type: 'blob' });
    }

    return items.slice(0, maxItems);
  }

  applyFilters(): void {
    const filt = (arr: QuizCard[]) => arr.filter(q =>
      (!this.searchTerm ||
        q.title.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        q.description.toLowerCase().includes(this.searchTerm.toLowerCase())
      ) &&
      (!this.selectedCategory || q.category === this.selectedCategory) &&
      (!this.selectedDifficulty || q.difficulty === this.selectedDifficulty)
    );

    this.popularQuizzes = filt(this.originalPopularQuizzes);
    this.myQuizzes      = filt(this.originalMyQuizzes);
    this.recentQuizzes  = filt(this.originalRecentQuizzes);
    this.popularCurrentPage = this.myQuizzesCurrentPage = this.recentCurrentPage = 1;

    this.categories = this.originalCategories
      .map(cat => ({
        ...cat,
        quizzes: filt(cat.quizzes),
        totalItems: filt(cat.quizzes).length,
        currentPage: 1
      }))
      .filter(cat => !this.selectedCategory || cat.name === this.selectedCategory);

    this.calculateAllBlobs();
    this.cdr.markForCheck();
  }
}
