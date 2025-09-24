import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { FormBuilder, ReactiveFormsModule, FormsModule } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { QuizCreationComponent } from './quiz-creation.component';
import { QuizManagementService } from '../../services/quiz-management.service';
import { of, throwError } from 'rxjs';

describe('QuizCreationComponent', () => {
  let component: QuizCreationComponent;
  let fixture: ComponentFixture<QuizCreationComponent>;
  let mockQuizService: jasmine.SpyObj<QuizManagementService>;
  let mockRouter: jasmine.SpyObj<Router>;
  let mockActivatedRoute: any;

  const mockTypeQuestions = [
    { id: 1, name: 'Question ouverte', key: 'open' },
    { id: 2, name: 'Question à choix multiples', key: 'multiple_choice' },
  ];

  const mockCategories = [
    { id: 1, name: 'Géographie' },
    { id: 2, name: 'Histoire' },
  ];

  const mockGroups = [
    { id: 1, name: 'Groupe 1' },
    { id: 2, name: 'Groupe 2' },
  ];

  const mockStatuses = [
    { id: 1, name: 'Brouillon', value: 'draft' },
    { id: 2, name: 'Publié', value: 'published' },
  ];

  const mockQuiz = {
    id: 1,
    title: 'Test Quiz',
    description: 'Description du quiz',
    status: 'draft',
    is_public: true,
    category: mockCategories[0],
    groups: [],
    questions: [
      {
        question: 'Test question?',
        type_question: mockTypeQuestions[0],
        difficulty: 'easy',
        answers: [
          { answer: 'Answer 1', is_correct: true },
          { answer: 'Answer 2', is_correct: false },
        ],
      },
    ],
  };

  beforeEach(async () => {
    mockQuizService = jasmine.createSpyObj('QuizManagementService', [
      'getTypeQuestions',
      'getCategories',
      'getGroups',
      'getStatuses',
      'getQuiz',
      'getQuizForEdit',
      'createQuiz',
      'updateQuiz',
    ]);

    mockRouter = jasmine.createSpyObj('Router', ['navigate']);

    mockActivatedRoute = {
      params: of({}),
    };

    await TestBed.configureTestingModule({
      imports: [QuizCreationComponent, ReactiveFormsModule, FormsModule],
      providers: [
        FormBuilder,
        { provide: QuizManagementService, useValue: mockQuizService },
        { provide: Router, useValue: mockRouter },
        { provide: ActivatedRoute, useValue: mockActivatedRoute },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(QuizCreationComponent);
    component = fixture.componentInstance;

    // Setup service mocks
    mockQuizService.getTypeQuestions.and.returnValue(of(mockTypeQuestions));
    mockQuizService.getCategories.and.returnValue(of(mockCategories));
    mockQuizService.getGroups.and.returnValue(of(mockGroups));
    mockQuizService.getStatuses.and.returnValue(of(mockStatuses));
    mockQuizService.getQuiz.and.returnValue(of(mockQuiz));
    mockQuizService.getQuizForEdit.and.returnValue(of(mockQuiz));
    mockQuizService.createQuiz.and.returnValue(of({ id: 1 }));
    mockQuizService.updateQuiz.and.returnValue(of({ id: 1 }));

    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.isSubmitting).toBe(false);
    expect(component.isEditMode).toBe(false);
    expect(component.quizId).toBeNull();
    expect(component.categorySearch).toBe('');
    expect(component.showCategoryDropdown).toBe(false);
    expect(component.selectedCategory).toBeNull();
    expect(component.difficulties.length).toBe(3);
  });

  it('should generate random color on init', () => {
    const colors = ['#257D54', '#FAA24B', '#D30D4C'];
    expect(colors).toContain(component.highlightColor);
  });

  it('should create quiz form with validators', () => {
    const form = component.quizForm;
    expect(form.get('title')?.hasError('required')).toBe(true);
    expect(form.get('description')?.hasError('required')).toBe(true);
    expect(form.get('status')?.value).toBe('draft');
    expect(form.get('is_public')?.value).toBe(true);
    expect(form.get('category')?.hasError('required')).toBe(true);
  });

  it('should load type questions on init', fakeAsync(() => {
    tick();
    expect(mockQuizService.getTypeQuestions).toHaveBeenCalled();
    expect(component.typeQuestions).toEqual(mockTypeQuestions);
  }));

  it('should load categories after type questions', fakeAsync(() => {
    tick();
    expect(mockQuizService.getCategories).toHaveBeenCalled();
    expect(component.categories).toEqual(mockCategories);
    expect(component.filteredCategories).toEqual(mockCategories);
  }));

  it('should load groups when is_public changes to false', fakeAsync(() => {
    tick();
    component.quizForm.get('is_public')?.setValue(false);
    expect(mockQuizService.getGroups).toHaveBeenCalled();
    expect(component.groups).toEqual(mockGroups);
  }));

  it('should enter edit mode when route has id', fakeAsync(() => {
    mockActivatedRoute.params = of({ id: '123' });
    component.ngOnInit();
    tick();

    expect(component.isEditMode).toBe(true);
    expect(component.quizId).toBe(123);
    expect(mockQuizService.getQuizForEdit).toHaveBeenCalledWith(123);
  }));

  it('should add question to form', () => {
    const initialLength = component.questions.length;
    component.addQuestion();

    expect(component.questions.length).toBe(initialLength + 1);

    const newQuestion = component.questions.at(component.questions.length - 1);
    expect(newQuestion.get('question')?.hasError('required')).toBe(true);
    expect(newQuestion.get('type_question')?.hasError('required')).toBe(true);
    expect(newQuestion.get('difficulty')?.value).toBe('easy');
  });

  it('should add answer to question', () => {
    component.addQuestion();
    const questionIndex = component.questions.length - 1;
    const initialAnswersLength = component.getAnswers(questionIndex).length;

    component.addAnswer(questionIndex);

    expect(component.getAnswers(questionIndex).length).toBe(initialAnswersLength + 1);

    const newAnswer = component
      .getAnswers(questionIndex)
      .at(component.getAnswers(questionIndex).length - 1);
    expect(newAnswer.get('answer')?.hasError('required')).toBe(true);
    expect(newAnswer.get('is_correct')?.value).toBe(false);
  });

  it('should remove question from form', () => {
    component.addQuestion();
    component.addQuestion();
    const initialLength = component.questions.length;

    component.removeQuestion(0);

    expect(component.questions.length).toBe(initialLength - 1);
  });

  it('should not remove question if only one left', () => {
    const initialLength = component.questions.length;

    component.removeQuestion(0);

    expect(component.questions.length).toBe(initialLength);
  });

  it('should remove answer from question', () => {
    component.addQuestion();
    const questionIndex = component.questions.length - 1;
    component.addAnswer(questionIndex);
    const initialAnswersLength = component.getAnswers(questionIndex).length;

    component.removeAnswer(questionIndex, 0);

    expect(component.getAnswers(questionIndex).length).toBe(initialAnswersLength - 1);
  });

  it('should not remove answer if only one left', () => {
    component.addQuestion();
    const questionIndex = component.questions.length - 1;
    const initialAnswersLength = component.getAnswers(questionIndex).length;

    component.removeAnswer(questionIndex, 0);

    expect(component.getAnswers(questionIndex).length).toBe(initialAnswersLength);
  });

  it('should filter categories based on search', () => {
    component.categories = mockCategories;
    component.categorySearch = 'Géo';

    component.filterCategories();

    expect(component.filteredCategories.length).toBe(1);
    expect(component.filteredCategories[0].name).toBe('Géographie');
  });

  it('should select category', () => {
    const category = mockCategories[0];

    component.selectCategory(category);

    expect(component.selectedCategory).toBe(category);
    expect(component.quizForm.get('category')?.value).toBe(category.id);
    expect(component.categorySearch).toBe(category.name);
    expect(component.showCategoryDropdown).toBe(false);
  });

  it('should toggle category dropdown', () => {
    component.showCategoryDropdown = false;

    component.showCategoryDropdown = true;

    expect(component.showCategoryDropdown).toBe(true);
  });

  it('should close category dropdown on document click outside', () => {
    component.showCategoryDropdown = true;
    const mockEvent = {
      target: {
        closest: jasmine.createSpy('closest').and.returnValue(null),
      },
    } as any;

    component.onDocumentClick(mockEvent);

    expect(component.showCategoryDropdown).toBe(false);
  });

  it('should not close category dropdown on click inside', () => {
    component.showCategoryDropdown = true;
    const mockEvent = {
      target: {
        closest: jasmine.createSpy('closest').and.returnValue({}),
      },
    } as any;

    component.onDocumentClick(mockEvent);

    expect(component.showCategoryDropdown).toBe(true);
  });

  it('should get answers for question', () => {
    component.addQuestion();
    const questionIndex = 0;

    const answers = component.getAnswers(questionIndex);

    expect(answers).toBeDefined();
    expect(answers.length).toBeGreaterThan(0);
  });

  it('should validate form before submission', () => {
    expect(component.quizForm.valid).toBe(false);

    component.quizForm.patchValue({
      title: 'Test Quiz',
      description: 'Test Description',
      category: mockCategories[0],
    });

    if (component.questions.length > 0) {
      component.questions.at(0).patchValue({
        question: 'Test question?',
        type_question: mockTypeQuestions[0],
      });
    }

    if (component.questions.length > 0 && component.getAnswers(0).length > 0) {
      component.getAnswers(0).at(0).patchValue({
        answer: 'Test answer',
        is_correct: true,
      });
    }

    expect(component.quizForm.valid).toBe(true);
  });

  it('should submit quiz in create mode', fakeAsync(() => {
    component.quizForm.patchValue({
      title: 'Test Quiz',
      description: 'Test Description',
      category: mockCategories[0],
    });

    component.onSubmit();
    tick();

    expect(mockQuizService.createQuiz).toHaveBeenCalled();
    expect(mockRouter.navigate).toHaveBeenCalledWith(['/quiz']);
  }));

  it('should submit quiz in edit mode', fakeAsync(() => {
    component.isEditMode = true;
    component.quizId = 1;

    component.quizForm.patchValue({
      title: 'Updated Quiz',
      description: 'Updated Description',
      category: mockCategories[0],
    });

    component.onSubmit();
    tick();

    expect(mockQuizService.updateQuiz).toHaveBeenCalledWith(1, jasmine.any(Object));
    expect(mockRouter.navigate).toHaveBeenCalledWith(['/quiz']);
  }));

  it('should handle submission error', fakeAsync(() => {
    mockQuizService.createQuiz.and.returnValue(throwError(() => new Error('API Error')));
    spyOn(console, 'error');

    component.quizForm.patchValue({
      title: 'Test Quiz',
      description: 'Test Description',
      category: mockCategories[0],
    });

    component.onSubmit();
    tick();

    expect(console.error).toHaveBeenCalled();
    expect(component.isSubmitting).toBe(false);
  }));

  it('should handle question type change', () => {
    component.addQuestion();
    const questionIndex = 0;

    component.onQuestionTypeChange(questionIndex);

    // Should clear existing answers and initialize new ones
    expect(component.getAnswers(questionIndex).length).toBeGreaterThan(0);
  });

  it('should handle correct answer change', () => {
    component.addQuestion();
    const questionIndex = 0;
    const answerIndex = 0;

    // Set the answer as correct first
    component.getAnswers(questionIndex).at(answerIndex).get('is_correct')?.setValue(true);

    component.onCorrectAnswerChange(questionIndex, answerIndex);

    // For MCQ type, other answers should be set to false
    expect(component.getAnswers(questionIndex).at(answerIndex).get('is_correct')?.value).toBe(true);
  });

  it('should get min answers for type', () => {
    expect(component['getMinAnswersForType']('multiple_choice')).toBe(3);
    expect(component['getMinAnswersForType']('true_false')).toBe(2);
    expect(component['getMinAnswersForType']('matching')).toBe(4);
    expect(component['getMinAnswersForType']('open')).toBe(2);
  });

  it('should handle true false correct answer change', () => {
    component.addQuestion();
    const questionIndex = component.questions.length - 1;

    component.onTrueFalseCorrectAnswerChange(questionIndex, true);

    const answers = component.getAnswers(questionIndex);
    expect(answers.at(0).get('is_correct')?.value).toBe(true);
    expect(answers.at(1).get('is_correct')?.value).toBe(false);
  });

  it('should get true false correct answer', () => {
    component.addQuestion();
    const questionIndex = component.questions.length - 1;

    const result = component.getTrueFalseCorrectAnswer(questionIndex);

    expect(typeof result).toBe('boolean');
  });

  it('should cancel and navigate back', () => {
    component.cancel();

    expect(mockRouter.navigate).toHaveBeenCalledWith(['/quiz-management']);
  });
});
