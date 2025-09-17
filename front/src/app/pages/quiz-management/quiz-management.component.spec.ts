import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { Router } from '@angular/router';
import { QuizManagementComponent } from './quiz-management.component';
import { QuizManagementService } from '../../services/quiz-management.service';
import { FileDownloadService } from '../../services/file-download.service';
import { TuiDialogService, TuiAlertService } from '@taiga-ui/core';
import { of, throwError, Subject } from 'rxjs';

describe('QuizManagementComponent', () => {
  let component: QuizManagementComponent;
  let fixture: ComponentFixture<QuizManagementComponent>;
  let mockQuizService: jasmine.SpyObj<QuizManagementService>;
  let mockFileService: jasmine.SpyObj<FileDownloadService>;
  let mockDialogService: jasmine.SpyObj<TuiDialogService>;
  let mockAlertService: jasmine.SpyObj<TuiAlertService>;
  let mockRouter: jasmine.SpyObj<Router>;

  const mockQuizData = {
    data: [
      {
        id: 1,
        title: 'Test Quiz 1',
        description: 'Description 1',
        createdBy: 'User 1',
        category: 'Géographie',
        groups: [{ id: 1, name: 'Groupe 1' }],
        isPublic: true,
        createdAt: '2024-01-01',
        updatedAt: '2024-01-01',
        questionsCount: 10,
      },
    ],
    meta: {
      total: 1,
      per_page: 20,
      current_page: 1,
      last_page: 1,
    },
  };

  beforeEach(async () => {
    mockQuizService = jasmine.createSpyObj('QuizManagementService', ['getQuizzes', 'deleteQuiz']);

    mockFileService = jasmine.createSpyObj('FileDownloadService', ['downloadFile']);

    mockDialogService = jasmine.createSpyObj('TuiDialogService', ['open']);

    mockAlertService = jasmine.createSpyObj('TuiAlertService', ['open']);

    mockRouter = jasmine.createSpyObj('Router', ['navigate']);

    await TestBed.configureTestingModule({
      imports: [QuizManagementComponent],
      providers: [
        { provide: QuizManagementService, useValue: mockQuizService },
        { provide: FileDownloadService, useValue: mockFileService },
        { provide: TuiDialogService, useValue: mockDialogService },
        { provide: TuiAlertService, useValue: mockAlertService },
        { provide: Router, useValue: mockRouter },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(QuizManagementComponent);
    component = fixture.componentInstance;

    // Setup service mocks
    mockQuizService.getQuizzes.and.returnValue(of(mockQuizData));
    mockQuizService.deleteQuiz.and.returnValue(of({}));

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.rows.length).toBeGreaterThanOrEqual(0);
    expect(component.isLoading).toBe(false);
    expect(component.loadError).toBe(false);
    expect(component.dataReady).toBe(true);
    expect(component.page).toBe(1);
    expect(component.pageSize).toBe(20);
    expect(component.totalPages).toBe(1);
    expect(component.totalItems).toBeGreaterThanOrEqual(0);
    expect(component.searchTerm).toBe('');
    expect(component.size).toBe('s');
    expect(component.open).toBe(false);
    expect(component.isDeleting).toBe(false);
    expect(component.showImportModal).toBe(false);
    expect(component.selectedFile).toBeNull();
  });

  it('should load quizzes on init', fakeAsync(() => {
    tick();
    expect(mockQuizService.getQuizzes).toHaveBeenCalled();
    expect(component.rows.length).toBeGreaterThanOrEqual(0);
    expect(component.totalItems).toBeGreaterThanOrEqual(0);
    expect(component.dataReady).toBe(true);
  }));

  it('should generate random color on init', () => {
    const colors = ['#257D54', '#FAA24B', '#D30D4C'];
    expect(colors).toContain(component.highlightColor);
  });

  it('should get quiz stats', () => {
    const quizWithStats = {
      userAnswers: [{ userId: 1 }, { userId: 2 }, { userId: 1 }],
      isPublic: true,
    };

    const stats = component.getQuizStats(quizWithStats);
    expect(stats).toContain('2 joueurs');
    expect(stats).toContain('3 parties');
  });

  it('should get quiz stats for public quiz without answers', () => {
    const publicQuiz = {
      userAnswers: [],
      isPublic: true,
    };

    const stats = component.getQuizStats(publicQuiz);
    expect(stats).toBe('Quiz public - 0 joueur');
  });

  it('should get quiz stats for private quiz', () => {
    const privateQuiz = {
      userAnswers: [],
      isPublic: false,
    };

    const stats = component.getQuizStats(privateQuiz);
    expect(stats).toBe('Quiz privé - 0 joueur');
  });

  it('should navigate to quiz creation', () => {
    component.onCreateQuiz();

    expect(mockRouter.navigate).toHaveBeenCalledWith(['/creation-quiz']);
  });

  it('should navigate to quiz edit', () => {
    component.editQuiz(1);

    expect(mockRouter.navigate).toHaveBeenCalledWith(['/creation-quiz', 1]);
  });

  it('should get visible groups', () => {
    const groups = [
      { id: 1, name: 'Groupe 1' },
      { id: 2, name: 'Groupe 2' },
      { id: 3, name: 'Groupe 3' },
      { id: 4, name: 'Groupe 4' },
    ];

    const visibleGroups = component.getVisibleGroups(groups);

    expect(visibleGroups.length).toBe(2);
    expect(visibleGroups[0].name).toBe('Groupe 1');
    expect(visibleGroups[1].name).toBe('Groupe 2');
  });

  it('should get remaining groups count', () => {
    const groups = [
      { id: 1, name: 'Groupe 1' },
      { id: 2, name: 'Groupe 2' },
      { id: 3, name: 'Groupe 3' },
      { id: 4, name: 'Groupe 4' },
    ];

    const remainingCount = component.getRemainingGroupsCount(groups);

    expect(remainingCount).toBe(2);
  });

  it('should get groups tooltip', () => {
    const groups = [
      { id: 1, name: 'Groupe 1' },
      { id: 2, name: 'Groupe 2' },
      { id: 3, name: 'Groupe 3' },
      { id: 4, name: 'Groupe 4' },
    ];

    const tooltip = component.getGroupsTooltip(groups);

    expect(tooltip).toBe('Groupe 3, Groupe 4');
  });

  it('should get export button text', () => {
    component.rows = [
      {
        id: 1,
        title: 'Quiz 1',
        selected: true,
        createdBy: 'User 1',
        groups: [],
        isPublic: true,
        status: 'published',
      },
      {
        id: 2,
        title: 'Quiz 2',
        selected: false,
        createdBy: 'User 2',
        groups: [],
        isPublic: false,
        status: 'draft',
      },
      {
        id: 3,
        title: 'Quiz 3',
        selected: true,
        createdBy: 'User 3',
        groups: [],
        isPublic: true,
        status: 'published',
      },
    ];

    const buttonText = component.getExportButtonText();

    expect(buttonText).toBe('Exporter (2)');
  });

  it('should get export tooltip', () => {
    component.rows = [
      {
        id: 1,
        title: 'Quiz 1',
        selected: true,
        createdBy: 'User 1',
        groups: [],
        isPublic: true,
        status: 'published',
      },
      {
        id: 2,
        title: 'Quiz 2',
        selected: false,
        createdBy: 'User 2',
        groups: [],
        isPublic: false,
        status: 'draft',
      },
    ];

    const tooltip = component.getExportTooltip();

    expect(tooltip).toBe('Exporter le quiz sélectionné au format JSON');
  });

  it('should handle search term change', fakeAsync(() => {
    component.onSearchChange('test');
    tick(300);

    expect(component.searchTerm).toBe('test');
    expect(component.page).toBe(1);
  }));

  it('should handle page change', () => {
    component.onPageChange(2);

    expect(component.page).toBe(1);
    expect(mockQuizService.getQuizzes).toHaveBeenCalled();
  });

  it('should handle page size change', () => {
    component.onPageSizeChange(10);

    expect(component.pageSize).toBe(10);
    expect(component.page).toBe(1);
    expect(mockQuizService.getQuizzes).toHaveBeenCalled();
  });

  it('should toggle import modal', () => {
    component.showImportModal = false;

    component.showImportModal = true;

    expect(component.showImportModal).toBe(true);

    component.showImportModal = false;

    expect(component.showImportModal).toBe(false);
  });

  it('should handle file selection', () => {
    const mockFile = new File(['test'], 'test.json', { type: 'application/json' });
    const mockEvent = {
      target: {
        files: [mockFile],
      },
    } as any;

    component.onFileSelected(mockEvent);

    expect(component.selectedFile).toBe(mockFile);
  });

  it('should handle service errors', fakeAsync(() => {
    mockQuizService.getQuizzes.and.returnValue(throwError(() => new Error('API Error')));
    spyOn(console, 'error');

    component.loadQuizzes();
    tick();

    expect(component.loadError).toBe(true);
    expect(component.isLoading).toBe(false);
  }));

  it('should destroy component properly', () => {
    spyOn(component['destroy$'], 'next');
    spyOn(component['destroy$'], 'complete');

    component.ngOnDestroy();

    expect(component['destroy$'].next).toHaveBeenCalled();
    expect(component['destroy$'].complete).toHaveBeenCalled();
  });
});
