import { TestBed } from '@angular/core/testing';
import { QuizTransitionService } from './quiz-transition.service';
import { QuizCard } from '../models/quiz.model';

describe('QuizTransitionService', () => {
  let service: QuizTransitionService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(QuizTransitionService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should have initial values', () => {
    let showValue: boolean = false;
    let quizValue: QuizCard | null = null;
    let positionValue: any = null;
    let exitingValue: boolean = false;

    service.showTransition$.subscribe(show => (showValue = show));
    service.currentQuiz$.subscribe(quiz => (quizValue = quiz));
    service.cardPosition$.subscribe(position => (positionValue = position));
    service.isExiting$.subscribe(exiting => (exitingValue = exiting));

    expect(showValue).toBe(false);
    expect(quizValue).toBeNull();
    expect(positionValue).toBeNull();
    expect(exitingValue).toBe(false);
  });

  it('should start transition', () => {
    const mockQuiz = { id: 1, title: 'Test Quiz' } as QuizCard;
    const mockElement = document.createElement('div');

    service.startTransition(mockQuiz, mockElement, 'red');

    let showValue: boolean = false;
    let quizValue: any = null;

    service.showTransition$.subscribe(show => (showValue = show));
    service.currentQuiz$.subscribe(quiz => (quizValue = quiz));

    expect(showValue).toBe(true);
    expect(quizValue).toEqual(mockQuiz);
  });

  it('should have observables', () => {
    expect(service.showTransition$).toBeDefined();
    expect(service.currentQuiz$).toBeDefined();
    expect(service.cardPosition$).toBeDefined();
    expect(service.isExiting$).toBeDefined();
  });

  it('should handle quiz data', () => {
    const mockQuiz = { id: 1, title: 'Test Quiz' } as QuizCard;
    service['_currentQuiz'].next(mockQuiz);

    let quizValue: any = null;
    service.currentQuiz$.subscribe(quiz => (quizValue = quiz));

    expect(quizValue).toEqual(mockQuiz);
  });
});
