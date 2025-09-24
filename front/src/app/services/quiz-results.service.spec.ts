import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { of, throwError } from 'rxjs';

import {
  QuizResultsService,
  QuizResult,
  QuizRating,
  QuizLeaderboard,
} from './quiz-results.service';
import { AuthService } from './auth.service';
import { environment } from '../../environments/environment';

describe('QuizResultsService', () => {
  let service: QuizResultsService;
  let httpMock: HttpTestingController;
  let mockAuthService: jasmine.SpyObj<AuthService>;

  beforeEach(() => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', ['isGuest', 'isLoggedIn']);
    authServiceSpy.isGuest.and.returnValue(false);
    authServiceSpy.isLoggedIn.and.returnValue(true);

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [QuizResultsService, { provide: AuthService, useValue: authServiceSpy }],
    });
    service = TestBed.inject(QuizResultsService);
    httpMock = TestBed.inject(HttpTestingController);
    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should save quiz result for logged in user', () => {
    const result: QuizResult = { quizId: 1, score: 85 };

    service.saveQuizResult(result).subscribe(response => {
      expect(response).toBeTruthy();
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/game-result`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      quiz_id: result.quizId,
      total_score: result.score,
    });
    req.flush({ success: true });
  });

  it('should save quiz result for guest user', () => {
    mockAuthService.isGuest.and.returnValue(true);

    const result: QuizResult = { quizId: 1, score: 75 };

    service.saveQuizResult(result).subscribe(response => {
      expect(response.success).toBe(true);
      expect(response.saved).toBe(false);
      expect(response.score).toBe(result.score);
    });

    expect(sessionStorage.getItem('guest-quiz-score')).toBe(result.score.toString());
  });

  it('should get quiz leaderboard for logged in user', () => {
    const quizId = 1;
    const mockLeaderboard: QuizLeaderboard = {
      leaderboard: [
        { rank: 1, name: 'John Doe', company: 'Test Corp', score: 95, isCurrentUser: false },
        { rank: 2, name: 'Jane Smith', company: 'Test Corp', score: 90, isCurrentUser: false },
      ],
      currentUserRank: 3,
      currentUserScore: 85,
      totalPlayers: 10,
    };

    service.getQuizLeaderboard(quizId).subscribe(leaderboard => {
      expect(leaderboard).toEqual(mockLeaderboard);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/quiz/${quizId}/leaderboard`);
    expect(req.request.method).toBe('GET');
    req.flush(mockLeaderboard);
  });

  it('should get quiz leaderboard for guest user', () => {
    mockAuthService.isGuest.and.returnValue(true);
    const savedScore = 75;
    sessionStorage.setItem('guest-quiz-score', savedScore.toString());

    const quizId = 1;
    const mockRealLeaderboard: QuizLeaderboard = {
      leaderboard: [
        { rank: 1, name: 'John Doe', company: 'Test Corp', score: 95, isCurrentUser: false },
        { rank: 2, name: 'Jane Smith', company: 'Test Corp', score: 90, isCurrentUser: false },
      ],
      currentUserRank: 0,
      currentUserScore: 0,
      totalPlayers: 2,
    };

    service.getQuizLeaderboard(quizId).subscribe(leaderboard => {
      expect(leaderboard.leaderboard.length).toBeGreaterThan(0);
      expect(leaderboard.currentUserScore).toBe(savedScore);
      expect(leaderboard.totalPlayers).toBeGreaterThan(mockRealLeaderboard.totalPlayers);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/quiz/${quizId}/public-leaderboard`);
    expect(req.request.method).toBe('GET');
    req.flush(mockRealLeaderboard);
  });

  it('should rate quiz', () => {
    const rating: QuizRating = { quizId: 1, rating: 4 };

    service.rateQuiz(rating).subscribe(response => {
      expect(response).toBeTruthy();
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/rate-quiz`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      quizId: rating.quizId,
      rating: rating.rating,
    });
    req.flush({ success: true });
  });

  it('should get quiz rating', () => {
    const quizId = 1;
    const mockRating = { averageRating: 4.2, totalRatings: 15 };

    service.getQuizRating(quizId).subscribe(rating => {
      expect(rating).toEqual(mockRating);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/quiz/${quizId}/rating`);
    expect(req.request.method).toBe('GET');
    req.flush(mockRating);
  });

  it('should handle HTTP error when saving quiz result', () => {
    const result: QuizResult = { quizId: 1, score: 85 };

    service.saveQuizResult(result).subscribe({
      error: error => {
        expect(error).toBeTruthy();
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-answer/game-result`);
    req.flush('Error', { status: 500, statusText: 'Server Error' });
  });
});
