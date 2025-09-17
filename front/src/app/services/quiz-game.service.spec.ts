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
    sessionStorage.clear();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should load quiz successfully', () => {
    const mockQuiz = {
      id: 1,
      title: 'Test Quiz',
      description: 'Test Description',
      questions: [],
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
    const mockResponse = { success: true, saved: true, score: 85 };

    service.saveGameResult(1, 85).subscribe(result => {
      expect(result).toEqual(mockResponse);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/game-result`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      quiz_id: 1,
      total_score: 85,
    });
    req.flush(mockResponse);
  });

  it('should save game result for guest user in sessionStorage', () => {
    mockAuthService.isGuest.and.returnValue(true);

    service.saveGameResult(1, 75).subscribe(result => {
      expect(result).toEqual({ success: true, saved: false, score: 75 });
    });

    expect(sessionStorage.getItem('guest-quiz-score')).toBe('75');
    httpMock.expectNone(`${environment.apiBaseUrl}/user-answer/game-result`);
  });

  it('should handle save game result error for authenticated user', () => {
    mockAuthService.isGuest.and.returnValue(false);

    service.saveGameResult(1, 85).subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error.status).toBe(500);
        expect(error.statusText).toBe('Internal Server Error');
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/game-result`);
    req.flush({ error: 'Server error' }, { status: 500, statusText: 'Internal Server Error' });
  });

  it('should load quiz with different ID', () => {
    const mockQuiz = {
      id: 2,
      title: 'Another Quiz',
      description: 'Another Description',
      questions: [],
    };

    service.loadQuiz(2).subscribe(quiz => {
      expect(quiz).toEqual(mockQuiz);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/quiz/2`);
    expect(req.request.method).toBe('GET');
    req.flush(mockQuiz);
  });

  it('should save game result with different score for guest', () => {
    mockAuthService.isGuest.and.returnValue(true);

    service.saveGameResult(3, 100).subscribe(result => {
      expect(result).toEqual({ success: true, saved: false, score: 100 });
    });

    expect(sessionStorage.getItem('guest-quiz-score')).toBe('100');
  });

  it('should save game result with zero score', () => {
    mockAuthService.isGuest.and.returnValue(false);
    const mockResponse = { success: true, saved: true, score: 0 };

    service.saveGameResult(1, 0).subscribe(result => {
      expect(result).toEqual(mockResponse);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/game-result`);
    expect(req.request.body).toEqual({
      quiz_id: 1,
      total_score: 0,
    });
    req.flush(mockResponse);
  });

  it('should save game result with negative score', () => {
    mockAuthService.isGuest.and.returnValue(false);
    const mockResponse = { success: true, saved: true, score: -10 };

    service.saveGameResult(1, -10).subscribe(result => {
      expect(result).toEqual(mockResponse);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/game-result`);
    expect(req.request.body).toEqual({
      quiz_id: 1,
      total_score: -10,
    });
    req.flush(mockResponse);
  });

  it('should handle load quiz error', () => {
    service.loadQuiz(999).subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error.status).toBe(404);
        expect(error.statusText).toBe('Not Found');
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/quiz/999`);
    req.flush({ error: 'Quiz not found' }, { status: 404, statusText: 'Not Found' });
  });

  it('should clear sessionStorage before saving new guest score', () => {
    mockAuthService.isGuest.and.returnValue(true);

    // Set initial value
    sessionStorage.setItem('guest-quiz-score', '50');

    service.saveGameResult(1, 90).subscribe();

    expect(sessionStorage.getItem('guest-quiz-score')).toBe('90');
  });
});
