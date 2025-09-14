import { ComponentFixture, TestBed } from '@angular/core/testing';
import { QuizTransitionComponent } from './quiz-transition.component';
import { QuizTransitionService } from '../../services/quiz-transition.service';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { of } from 'rxjs';

describe('QuizTransitionComponent', () => {
  let component: QuizTransitionComponent;
  let fixture: ComponentFixture<QuizTransitionComponent>;
  let mockQuizTransitionService: jasmine.SpyObj<QuizTransitionService>;

  const mockQuiz = {
    id: 1,
    title: 'Test Quiz',
    description: 'Test Description',
    category: 'Test Category',
    difficulty: 'Easy',
    duration: 30,
    questionCount: 5,
    isPublic: true,
    createdAt: new Date(),
    updatedAt: new Date(),
    authorId: 1,
    authorName: 'Test Author'
  };

  beforeEach(async () => {
    mockQuizTransitionService = jasmine.createSpyObj('QuizTransitionService', [], {
      showTransition$: of(false),
      currentQuiz$: of(null),
      cardPosition$: of(null),
      isExiting$: of(false),
      cardColor$: of('var(--color-primary)')
    });

    await TestBed.configureTestingModule({
      imports: [QuizTransitionComponent],
      providers: [
        { provide: QuizTransitionService, useValue: mockQuizTransitionService }
      ],
      schemas: [NO_ERRORS_SCHEMA]
    }).compileComponents();

    fixture = TestBed.createComponent(QuizTransitionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.showTransition).toBe(false);
    expect(component.currentQuiz).toBe(null);
    expect(component.cardPosition).toBe(null);
    expect(component.isExiting).toBe(false);
    expect(component.cardColor).toBe('var(--color-primary)');
  });

  it('should clean up subscriptions on destroy', () => {
    spyOn(component['subscriptions'], 'forEach');
    
    component.ngOnDestroy();
    
    expect(component['subscriptions'].forEach).toHaveBeenCalled();
  });
});