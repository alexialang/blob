import {
  Component,
  OnInit,
  OnDestroy,
  ChangeDetectionStrategy,
  ChangeDetectorRef,
} from '@angular/core';
import { CommonModule, NgOptimizedImage } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { QuizManagementService } from '../../services/quiz-management.service';
import { QuizResultsService } from '../../services/quiz-results.service';
import { QuizCardComponent } from '../../components/quiz-card/quiz-card.component';
import { QuizCard } from '../../models/quiz.model';
import { PaginationComponent } from '../../components/pagination/pagination.component';
import { forkJoin } from 'rxjs';
import { AuthService } from '../../services/auth.service';
import { AlertService } from '../../services/alert.service';

interface CategoryWithPagination {
  name: string;
  quizzes: QuizCard[];
  currentPage: number;
  itemsPerPage: number;
  pageSize: number;
  totalItems: number;
}

type QuizItem = { type: 'quiz'; data: QuizCard };
type BlobItem = { type: 'blob' };
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
export class QuizCardsComponent implements OnInit, OnDestroy {
  originalPopularQuizzes: QuizCard[] = [];
  popularQuizzes: QuizCard[] = [];
  popularQuizzesWithBlobs: DisplayItem[] = [];
  popularCurrentPage = 1;

  originalMyQuizzes: QuizCard[] = [];
  myQuizzes: QuizCard[] = [];
  myQuizzesWithBlobs: DisplayItem[] = [];
  myQuizzesCurrentPage = 1;

  originalGroupQuizzes: QuizCard[] = [];
  groupQuizzes: QuizCard[] = [];
  groupQuizzesWithBlobs: DisplayItem[] = [];
  groupQuizzesCurrentPage = 1;

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

  isGuest = false;
  currentUser: any = null;

  private readonly flippedCardsCache = new Map<number, boolean>();
  private readonly blobPositions: Record<string, number | null> = {};
  private ratingUpdateListener?: (event: Event) => void;

  constructor(
    private quizService: QuizManagementService,
    private quizResultsService: QuizResultsService,
    private cdr: ChangeDetectorRef,
    private router: Router,
    private authService: AuthService,
    private alertService: AlertService
  ) {}

  ngOnInit(): void {
    this.isGuest = this.authService.isGuest();
    this.loadQuizzes();
    this.setupRatingListener();
  }

  get isUserLoggedIn(): boolean {
    return this.authService.isLoggedIn();
  }

  ngOnDestroy(): void {
    if (this.ratingUpdateListener) {
      window.removeEventListener('quiz-rating-updated', this.ratingUpdateListener);
    }
  }

  private loadQuizzes(): void {
    this.quizService.getOrganizedQuizzes().subscribe({
      next: data => {
        this.originalPopularQuizzes = this.convertToQuizCards(data.popular ?? []);
        this.popularQuizzes = [...this.originalPopularQuizzes];

        if (this.isUserLoggedIn) {
          this.originalMyQuizzes = this.convertToQuizCards(data.myQuizzes ?? []);
          this.myQuizzes = [...this.originalMyQuizzes];

          this.originalGroupQuizzes = this.convertToQuizCards(data.groupQuizzes ?? []);
          this.groupQuizzes = [...this.originalGroupQuizzes];

          // Charger les informations de l'utilisateur connecté
          this.authService.getCurrentUser().subscribe(user => {
            this.currentUser = user;
            this.cdr.markForCheck();
          });

          // Si les quiz de l'utilisateur sont vides, utiliser l'API de fallback
          if (this.originalMyQuizzes.length === 0 && this.originalGroupQuizzes.length === 0) {
            this.loadMyQuizzesFromManagement();
          }
        } else {
          this.originalMyQuizzes = [];
          this.myQuizzes = [];
          this.originalGroupQuizzes = [];
          this.groupQuizzes = [];
        }

        this.originalRecentQuizzes = this.convertToQuizCards(data.recent ?? []);
        this.recentQuizzes = [...this.originalRecentQuizzes];

        this.originalCategories = (data.categories ?? []).map(
          (cat: any): CategoryWithPagination => ({
            ...cat,
            quizzes: this.convertToQuizCards(cat.quizzes ?? []),
            currentPage: 1,
            itemsPerPage: this.pageSize,
            totalItems: (cat.quizzes ?? []).length,
            totalPages: Math.ceil((cat.quizzes ?? []).length / this.pageSize),
          })
        );
        this.categories = [...this.originalCategories];

        this.calculateAllBlobs();
        this.loadQuizRatings();

        this.loading = false;
        this.cdr.markForCheck();
      },
      error: err => {
        this.loading = false;
        this.cdr.markForCheck();
      },
    });
  }

  private loadMyQuizzesFromManagement(): void {
    this.quizService.getAllQuizzes().subscribe({
      next: response => {
        const quizzes = (response as any)?.data || response;
        this.authService.getCurrentUser().subscribe(currentUser => {
          this.currentUser = currentUser;

          const myCreatedQuizzes: QuizCard[] = [];
          const myGroupQuizzes: QuizCard[] = [];

          quizzes.forEach((quiz: any) => {
            const isMyQuiz = quiz.user?.id === currentUser.id;

            if (isMyQuiz) {
              // Quiz créé par l'utilisateur
              if (quiz.isPublic) {
                myCreatedQuizzes.push(this.convertSingleQuizToCard(quiz));
              } else if (quiz.groups?.length > 0) {
                // Quiz privé avec groupes -> va dans les quiz de groupe
                myGroupQuizzes.push(this.convertSingleQuizToCard(quiz));
              } else {
                // Quiz privé sans groupes -> va dans mes quiz créés
                myCreatedQuizzes.push(this.convertSingleQuizToCard(quiz));
              }
            } else {
              // Quiz créé par d'autres utilisateurs
              if (!quiz.isPublic && quiz.groups?.length > 0) {
                // Vérifier si l'utilisateur fait partie d'un des groupes
                const userGroupIds = currentUser.groups?.map((g: any) => g.id) || [];
                const quizGroupIds = quiz.groups?.map((g: any) => g.id) || [];
                const hasCommonGroup = userGroupIds.some((id: number) => quizGroupIds.includes(id));

                if (hasCommonGroup) {
                  myGroupQuizzes.push(this.convertSingleQuizToCard(quiz));
                }
              }
            }
          });

          this.originalMyQuizzes = myCreatedQuizzes;
          this.myQuizzes = [...this.originalMyQuizzes];

          this.originalGroupQuizzes = myGroupQuizzes;
          this.groupQuizzes = [...this.originalGroupQuizzes];

          this.calculateAllBlobs();
          this.cdr.markForCheck();
        });
      },
      error: error => {
        console.error('Erreur lors du chargement des quiz depuis la gestion:', error);
      },
    });
  }

  private convertSingleQuizToCard(quiz: any): QuizCard {
    const companyName = quiz.company?.name ?? quiz.user?.company?.name ?? 'Inconnu';
    const groupName = quiz.groups?.[0]?.name ?? null;
    const isPublic = quiz.is_public ?? quiz.isPublic ?? false;

    let categoryName = 'Catégorie inconnue';
    if (quiz.category?.name) {
      categoryName = quiz.category.name;
    }

    let difficultyLabel = 'Inconnue';
    if (quiz.difficulty) {
      const difficultyMap: { [key: string]: string } = {
        easy: 'Facile',
        medium: 'Moyen',
        hard: 'Difficile',
      };
      difficultyLabel = difficultyMap[quiz.difficulty] || quiz.difficulty;
    }

    return {
      id: quiz.id,
      title: quiz.title,
      description: quiz.description,
      difficulty: difficultyLabel as 'Facile' | 'Moyen' | 'Difficile',
      category: quiz.category?.name || categoryName,
      is_public: isPublic,
      company: companyName,
      groupName,
      rating: 0,
      questionCount: 0,
      isFlipped: false,
      playMode: 'solo' as 'solo' | 'team',
    };
  }

  private convertToQuizCards(raw: any[]): QuizCard[] {
    return raw.map(q => {
      const companyName = q.company?.name ?? q.user?.company?.name ?? 'Inconnu';
      const groupName = q.groups?.[0]?.name ?? null;
      const isPublic = q.is_public ?? q.isPublic ?? false;

      let categoryName = 'Catégorie inconnue';
      if (q.category) {
        if (typeof q.category === 'string') {
          categoryName = q.category;
        } else if (q.category.name) {
          categoryName = q.category.name;
        } else {
          categoryName = JSON.stringify(q.category);
        }
      }

      let rating = 0;
      if (q.rating !== undefined && q.rating !== null) {
        rating = Math.round(q.rating);
      }

      return {
        id: q.id,
        title: q.title,
        description: q.description ?? 'Aucune description disponible',
        is_public: isPublic,
        company: companyName,
        groupName,
        category: categoryName,
        difficulty: q.difficultyLabel ?? q.difficulty ?? 'Niveau non renseigné',
        rating: rating,
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

  togglePlayMode(quiz: QuizCard): void {
    quiz.playMode = quiz.playMode === 'solo' ? 'team' : 'solo';
    this.cdr.markForCheck();
  }

  startQuiz(quiz: QuizCard): void {
    if (quiz.playMode === 'solo') {
      this.router.navigate(['/quiz', quiz.id, 'play']);
    } else if (quiz.playMode === 'team') {
      if (this.isGuest) {
        this.alertService.error('Vous devez vous connecter pour jouer en équipe !');
        this.router.navigate(['/connexion']);
        return;
      }
      this.router.navigate(['/multiplayer/create', quiz.id]);
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

  onGroupQuizzesPageChange(page: number): void {
    this.groupQuizzesCurrentPage = page;
    const key = `group-${page}`;
    this.groupQuizzesWithBlobs = this.addRandomBlobsLimited(
      this.getSectionPage(this.groupQuizzes, page),
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
    this.categoriesWithBlobs[key] = this.addRandomBlobsLimited(slice, cat.itemsPerPage, key);
  }

  private calculateAllBlobs(): void {
    this.onPopularPageChange(this.popularCurrentPage);
    this.onMyQuizzesPageChange(this.myQuizzesCurrentPage);
    this.onGroupQuizzesPageChange(this.groupQuizzesCurrentPage);
    this.onRecentPageChange(this.recentCurrentPage);
    this.categories.forEach(cat => this.recalculateCategoryBlobs(cat));
  }

  public addRandomBlobsLimited(quizzes: QuizCard[], maxItems: number, key: string): DisplayItem[] {
    const BLOB_PROBABILITY = 0.25;
    const MIN_QUIZZES_FOR_BLOB = 1;

    if (!(key in this.blobPositions)) {
      if (quizzes.length >= MIN_QUIZZES_FOR_BLOB && Math.random() < BLOB_PROBABILITY) {
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
    const filt = (arr: QuizCard[]) =>
      arr.filter(
        q =>
          (!this.searchTerm ||
            q.title.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
            q.description.toLowerCase().includes(this.searchTerm.toLowerCase())) &&
          (!this.selectedCategory || q.category === this.selectedCategory) &&
          (!this.selectedDifficulty || q.difficulty === this.selectedDifficulty)
      );

    this.popularQuizzes = filt(this.originalPopularQuizzes);
    this.myQuizzes = filt(this.originalMyQuizzes);
    this.recentQuizzes = filt(this.originalRecentQuizzes);
    this.popularCurrentPage = this.myQuizzesCurrentPage = this.recentCurrentPage = 1;

    this.categories = this.originalCategories
      .map(cat => ({
        ...cat,
        quizzes: filt(cat.quizzes),
        totalItems: filt(cat.quizzes).length,
        currentPage: 1,
      }))
      .filter(cat => !this.selectedCategory || cat.name === this.selectedCategory);

    this.calculateAllBlobs();
    this.cdr.markForCheck();
  }

  private loadQuizRatings(): void {
    const allQuizzes: QuizCard[] = [
      ...this.originalPopularQuizzes,
      ...this.originalMyQuizzes,
      ...this.originalRecentQuizzes,
      ...this.originalCategories.flatMap(cat => cat.quizzes),
    ];

    const uniqueQuizzes = allQuizzes.filter(
      (quiz, index, self) => index === self.findIndex(q => q.id === quiz.id)
    );

    if (uniqueQuizzes.length === 0) return;
    const ratingRequests = uniqueQuizzes.map(quiz =>
      this.quizResultsService.getPublicQuizRating(quiz.id)
    );

    forkJoin(ratingRequests).subscribe({
      next: ratings => {
        ratings.forEach((rating, index) => {
          const quizId = uniqueQuizzes[index].id;
          const displayRating = rating.averageRating || 0;

          this.updateQuizRating(this.originalPopularQuizzes, quizId, displayRating);
          this.updateQuizRating(this.popularQuizzes, quizId, displayRating);
          this.updateQuizRating(this.originalMyQuizzes, quizId, displayRating);
          this.updateQuizRating(this.myQuizzes, quizId, displayRating);
          this.updateQuizRating(this.originalRecentQuizzes, quizId, displayRating);
          this.updateQuizRating(this.recentQuizzes, quizId, displayRating);

          this.originalCategories.forEach(cat => {
            this.updateQuizRating(cat.quizzes, quizId, displayRating);
          });
          this.categories.forEach(cat => {
            this.updateQuizRating(cat.quizzes, quizId, displayRating);
          });
        });

        this.cdr.markForCheck();
      },
      error: error => {},
    });
  }

  private updateQuizRating(quizList: QuizCard[], quizId: number, rating: number): void {
    const quiz = quizList.find(q => q.id === quizId);
    if (quiz) {
      quiz.rating = rating;
    }
  }

  private setupRatingListener(): void {
    this.ratingUpdateListener = (event: Event) => {
      const customEvent = event as CustomEvent;
      const { quizId, rating } = customEvent.detail;

      this.updateQuizRating(this.originalPopularQuizzes, quizId, rating);
      this.updateQuizRating(this.popularQuizzes, quizId, rating);
      this.updateQuizRating(this.originalMyQuizzes, quizId, rating);
      this.updateQuizRating(this.myQuizzes, quizId, rating);
      this.updateQuizRating(this.originalRecentQuizzes, quizId, rating);
      this.updateQuizRating(this.recentQuizzes, quizId, rating);

      this.originalCategories.forEach(cat => {
        this.updateQuizRating(cat.quizzes, quizId, rating);
      });
      this.categories.forEach(cat => {
        this.updateQuizRating(cat.quizzes, quizId, rating);
      });

      this.cdr.markForCheck();
    };

    window.addEventListener('quiz-rating-updated', this.ratingUpdateListener);
  }

  getCardIndex(index: number): number {
    return index % 4;
  }

  getCardRowIndex(index: number): number {
    return Math.floor(index / 4);
  }

  getGroupNames(): string[] {
    if (!this.groupQuizzes.length) return [];

    const allGroups = new Set<string>();
    this.groupQuizzes.forEach(quiz => {
      if (quiz.groupName) {
        allGroups.add(quiz.groupName);
      }
    });

    return Array.from(allGroups);
  }
}
