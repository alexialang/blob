import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { QuizGameService } from './quiz-game.service';
import { AuthService } from './auth.service';
import { environment } from '../../environments/environment';

describe('QuizGameService', () => {
  let service: QuizGameService;
  let httpMock: HttpTestingController;
  let mockAuthService: jasmine.SpyObj<AuthService>;

  beforeEach(() => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', ['isGuest']);

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [QuizGameService, { provide: AuthService, useValue: authServiceSpy }],
    });
    service = TestBed.inject(QuizGameService);
    httpMock = TestBed.inject(HttpTestingController);
    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should load quiz data', () => {
    const mockQuiz = {
      id: 1,
      title: 'Test Quiz',
      description: 'Test Description',
      questions: [
        {
          id: 1,
          question: 'Test question',
          type_question: 'mcq',
          answers: [],
        },
      ],
    };

    service.loadQuiz(1).subscribe(quiz => {
      expect(quiz).toEqual(mockQuiz);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/quiz/1`);
    expect(req.request.method).toBe('GET');
    req.flush(mockQuiz);
  });

  it('should save game result for authenticated user', () => {
    mockAuthService.isGuest.and.returnValue(false);

    const mockResponse = {
      success: true,
      saved: true,
      score: 85,
    };

    service.saveGameResult(1, 85).subscribe(response => {
      expect(response).toEqual(mockResponse);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/game-result`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      quiz_id: 1,
      total_score: 85,
    });
    req.flush(mockResponse);
  });

  it('should save game result for guest user', () => {
    mockAuthService.isGuest.and.returnValue(true);

    service.saveGameResult(1, 75).subscribe(response => {
      expect(response).toEqual({
        success: true,
        saved: false,
        score: 75,
      });
    });

    expect(sessionStorage.getItem('guest-quiz-score')).toBe('75');
    httpMock.expectNone(`${environment.apiBaseUrl}/user-answer/game-result`);
  });

  it('should handle errors gracefully', () => {
    service.loadQuiz(999).subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error.status).toBe(404);
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/quiz/999`);
    req.flush('Quiz not found', { status: 404, statusText: 'Not Found' });
  });

  it('should handle save result errors', () => {
    mockAuthService.isGuest.and.returnValue(false);

    service.saveGameResult(1, 85).subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error.status).toBe(500);
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/game-result`);
    req.flush('Server error', { status: 500, statusText: 'Internal Server Error' });
  });

  it('should load quiz with different data structures', () => {
    const mockQuiz = {
      id: 2,
      title: 'Advanced Quiz',
      description: 'Advanced Description',
      questions: [
        {
          id: 1,
          question: 'Question 1',
          type_question: 'multiple_choice',
          answers: [
            { id: 1, answer: 'Answer 1', is_correct: true },
            { id: 2, answer: 'Answer 2', is_correct: false },
          ],
        },
        {
          id: 2,
          question: 'Question 2',
          type_question: 'true_false',
          answers: [
            { id: 3, answer: 'True', is_correct: false },
            { id: 4, answer: 'False', is_correct: true },
          ],
        },
      ],
    };

    service.loadQuiz(2).subscribe(quiz => {
      expect(quiz.questions.length).toBe(2);
      expect(quiz.questions[0].type_question).toBe('multiple_choice');
      expect(quiz.questions[1].type_question).toBe('true_false');
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/quiz/2`);
    req.flush(mockQuiz);
  });

  it('should save different score values for authenticated users', () => {
    mockAuthService.isGuest.and.returnValue(false);

    const testCases = [0, 50, 100];

    testCases.forEach((score, index) => {
      const mockResponse = {
        success: true,
        saved: true,
        score: score,
      };

      service.saveGameResult(index + 1, score).subscribe(response => {
        expect(response.score).toBe(score);
        expect(response.saved).toBe(true);
      });

      const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/game-result`);
      expect(req.request.body.total_score).toBe(score);
      req.flush(mockResponse);
    });
  });

  it('should handle guest mode with different scores', () => {
    mockAuthService.isGuest.and.returnValue(true);

    const scores = [25, 75, 100];

    scores.forEach(score => {
      service.saveGameResult(1, score).subscribe(response => {
        expect(response.score).toBe(score);
        expect(response.saved).toBe(false);
        expect(response.success).toBe(true);
      });

      expect(sessionStorage.getItem('guest-quiz-score')).toBe(score.toString());
      httpMock.expectNone(`${environment.apiBaseUrl}/user-answer/game-result`);
    });
  });
});
