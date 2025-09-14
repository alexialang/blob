import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ElementRef } from '@angular/core';

import { QuizCardComponent } from './quiz-card.component';
import { QuizTransitionService } from '../../services/quiz-transition.service';
import { AuthService } from '../../services/auth.service';
import { QuizCard } from '../../models/quiz.model';

describe('QuizCardComponent', () => {
  let component: QuizCardComponent;
  let fixture: ComponentFixture<QuizCardComponent>;
  let mockQuizTransitionService: jasmine.SpyObj<QuizTransitionService>;
  let mockAuthService: jasmine.SpyObj<AuthService>;

  beforeEach(async () => {
    const quizTransitionServiceSpy = jasmine.createSpyObj('QuizTransitionService', ['startTransition']);
    const authServiceSpy = jasmine.createSpyObj('AuthService', ['isLoggedIn', 'isGuest']);

    await TestBed.configureTestingModule({
      imports: [QuizCardComponent],
      providers: [
        { provide: QuizTransitionService, useValue: quizTransitionServiceSpy },
        { provide: AuthService, useValue: authServiceSpy },
        { provide: ElementRef, useValue: {} }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(QuizCardComponent);
    component = fixture.componentInstance;
    mockQuizTransitionService = TestBed.inject(QuizTransitionService) as jasmine.SpyObj<QuizTransitionService>;
    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
    mockAuthService.isGuest.and.returnValue(false);
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.index).toBe(0);
    expect(component.rowIndex).toBe(0);
    expect(component.cardColor).toBe('');
    expect(component.cardTextColor).toBe('');
  });

  it('should assign balanced color on init', () => {
    component.quiz = {
      id: 1,
      title: 'Test Quiz',
      description: 'Test Description',
      is_public: true,
      company: 'Test Company',
      category: 'Technology',
      difficulty: 'Facile',
      rating: 4.5,
      questionCount: 10,
      isFlipped: false,
      playMode: 'solo'
    } as QuizCard;

    component.ngOnInit();

    expect(component.cardColor).toBeTruthy();
    expect(component.cardTextColor).toBeTruthy();
  });

  it('should emit quizStart when startQuiz is called', async () => {
    spyOn(component.quizStart, 'emit');
    mockQuizTransitionService.startTransition.and.returnValue(Promise.resolve());
    component.quiz = {
      id: 1,
      title: 'Test Quiz',
      description: 'Test Description',
      is_public: true,
      company: 'Test Company',
      category: 'Technology',
      difficulty: 'Facile',
      rating: 4.5,
      questionCount: 10,
      isFlipped: false,
      playMode: 'solo'
    } as QuizCard;

    await component.startQuiz();

    expect(component.quizStart.emit).toHaveBeenCalledWith(component.quiz);
  });

  it('should emit quizFlip when flipCard is called', () => {
    spyOn(component.quizFlip, 'emit');
    component.quiz = {
      id: 1,
      title: 'Test Quiz',
      description: 'Test Description',
      is_public: true,
      company: 'Test Company',
      category: 'Technology',
      difficulty: 'Facile',
      rating: 4.5,
      questionCount: 10,
      isFlipped: false,
      playMode: 'solo'
    } as QuizCard;

    component.flipCard();

    expect(component.quizFlip.emit).toHaveBeenCalledWith(component.quiz);
  });

  it('should emit playModeChange when togglePlayMode is called', () => {
    spyOn(component.playModeChange, 'emit');
    component.quiz = {
      id: 1,
      title: 'Test Quiz',
      description: 'Test Description',
      is_public: true,
      company: 'Test Company',
      category: 'Technology',
      difficulty: 'Facile',
      rating: 4.5,
      questionCount: 10,
      isFlipped: false,
      playMode: 'solo'
    } as QuizCard;

    component.togglePlayMode();

    expect(component.playModeChange.emit).toHaveBeenCalledWith(component.quiz);
  });

  it('should have correct color palette', () => {
    expect(component['colorPalette']).toEqual([
      { bg: 'var(--color-primary)', text: 'white' },
      { bg: 'var(--color-secondary-dark)', text: 'white' },
      { bg: 'var(--color-accent-dark)', text: 'white' },
      { bg: 'var(--color-pink-dark)', text: 'white' },
    ]);
  });

  it('should handle different index values', () => {
    component.index = 5;
    component.rowIndex = 2;
    
    expect(component.index).toBe(5);
    expect(component.rowIndex).toBe(2);
  });

  it('should return group name when quiz is not public and has groupName', () => {
    component.quiz = {
      id: 1,
      title: 'Test Quiz',
      description: 'Test Description',
      is_public: false,
      company: 'Test Company',
      category: 'Technology',
      difficulty: 'Facile',
      rating: 4.5,
      questionCount: 10,
      isFlipped: false,
      playMode: 'solo',
      groupName: 'Test Group'
    } as QuizCard;

    expect(component.getCompanyBadgeText()).toBe('Test Group');
  });

  it('should return empty string when quiz is public', () => {
    component.quiz = {
      id: 1,
      title: 'Test Quiz',
      description: 'Test Description',
      is_public: true,
      company: 'Test Company',
      category: 'Technology',
      difficulty: 'Facile',
      rating: 4.5,
      questionCount: 10,
      isFlipped: false,
      playMode: 'solo',
      groupName: 'Test Group'
    } as QuizCard;

    expect(component.getCompanyBadgeText()).toBe('');
  });
});
