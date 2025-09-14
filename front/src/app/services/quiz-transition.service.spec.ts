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
    service.showTransition$.subscribe(show => expect(show).toBe(false));
    service.currentQuiz$.subscribe(quiz => expect(quiz).toBeNull());
    service.cardPosition$.subscribe(position => expect(position).toBeNull());
    service.isExiting$.subscribe(exiting => expect(exiting).toBe(false));
  });

  it('should start transition', () => {
    const mockQuiz = { id: 1, title: 'Test Quiz' } as QuizCard;
    const mockElement = document.createElement('div');
    
    service.startTransition(mockQuiz, mockElement, 'red');
    
    service.showTransition$.subscribe(show => expect(show).toBe(true));
    service.currentQuiz$.subscribe(quiz => expect(quiz).toEqual(mockQuiz));
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
    service.currentQuiz$.subscribe(quiz => expect(quiz).toEqual(mockQuiz));
  });
});
