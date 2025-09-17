import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { of, throwError } from 'rxjs';

import {
  GlobalStatisticsService,
  GlobalStatistics,
  CompanyStatistics,
} from './global-statistics.service';
import { AuthService } from './auth.service';
import { environment } from '../../environments/environment';

describe('GlobalStatisticsService', () => {
  let service: GlobalStatisticsService;
  let httpMock: HttpTestingController;
  let mockAuthService: jasmine.SpyObj<AuthService>;

  beforeEach(() => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', ['isAdmin', 'hasPermission']);

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [GlobalStatisticsService, { provide: AuthService, useValue: authServiceSpy }],
    });

    service = TestBed.inject(GlobalStatisticsService);
    httpMock = TestBed.inject(HttpTestingController);
    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should get global statistics for admin user', () => {
    const mockGlobalStats: GlobalStatistics = {
      totalUsers: 1000,
      totalQuizzes: 50,
      globalAverageScore: 85.5,
      totalGamesPlayed: 5000,
      topEmployees: [
        {
          id: 1,
          pseudo: 'topuser',
          firstName: 'Top',
          lastName: 'User',
          averageScore: 95.0,
          quizzesCompleted: 20,
          companyName: 'Test Company',
        },
      ],
      companyStats: [
        {
          id: 1,
          name: 'Test Company',
          averageScore: 88.0,
          totalEmployees: 100,
          totalQuizzes: 10,
        },
      ],
      quizPerformance: [
        {
          id: 1,
          title: 'Test Quiz',
          averageScore: 82.0,
          totalAttempts: 200,
          category: 'Technology',
        },
      ],
      groupStats: [
        {
          id: 1,
          name: 'Development Team',
          description: 'Software developers',
          companyName: 'Test Company',
          totalMembers: 15,
          averageScore: 90.0,
          totalQuizzes: 8,
        },
      ],
    };

    mockAuthService.isAdmin.and.returnValue(of(true));

    service.getGlobalStatistics().subscribe(stats => {
      expect(stats).toEqual(mockGlobalStats);
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/api/global-statistics`);
    expect(req.request.method).toBe('GET');
    req.flush(mockGlobalStats);
  });

  it('should throw error for non-admin user', () => {
    mockAuthService.isAdmin.and.returnValue(of(false));

    service.getGlobalStatistics().subscribe({
      next: () => fail('Should have thrown error'),
      error: error => {
        expect(error.message).toBe('Rôle ADMIN requis');
      },
    });
  });

  it('should handle admin check error', () => {
    mockAuthService.isAdmin.and.returnValue(throwError(() => new Error('Auth error')));

    service.getGlobalStatistics().subscribe({
      next: () => fail('Should have thrown error'),
      error: error => {
        expect(error.message).toBe('Auth error');
      },
    });
  });

  it('should get company statistics with permission', () => {
    const companyId = 1;
    const mockCompanyStats: CompanyStatistics = {
      teamScores: [
        {
          quizTitle: 'Team Quiz',
          quizId: 1,
          averageScore: 85.0,
          participants: 20,
        },
      ],
      groupScores: {
        Development: [
          {
            quizTitle: 'Dev Quiz',
            quizId: 2,
            averageScore: 90.0,
            participants: 10,
          },
        ],
      },
    };

    mockAuthService.hasPermission.and.returnValue(of(true));

    service.getCompanyStatistics(companyId).subscribe(stats => {
      expect(stats).toEqual(mockCompanyStats);
    });

    const req = httpMock.expectOne(
      `${environment.apiUrl}/api/global-statistics/company/${companyId}`
    );
    expect(req.request.method).toBe('GET');
    req.flush(mockCompanyStats);
  });

  it('should get company statistics with timestamp', () => {
    const companyId = 1;
    const timestamp = 1234567890;
    const mockCompanyStats: CompanyStatistics = {
      teamScores: [],
      groupScores: {},
    };

    mockAuthService.hasPermission.and.returnValue(of(true));

    service.getCompanyStatistics(companyId, timestamp).subscribe(stats => {
      expect(stats).toEqual(mockCompanyStats);
    });

    const req = httpMock.expectOne(
      `${environment.apiUrl}/api/global-statistics/company/${companyId}?t=${timestamp}`
    );
    expect(req.request.method).toBe('GET');
    req.flush(mockCompanyStats);
  });

  it('should throw error for user without VIEW_RESULTS permission', () => {
    const companyId = 1;
    mockAuthService.hasPermission.and.returnValue(of(false));

    service.getCompanyStatistics(companyId).subscribe({
      next: () => fail('Should have thrown error'),
      error: error => {
        expect(error.message).toBe('Permission VIEW_RESULTS requise');
      },
    });
  });

  it('should handle permission check error', () => {
    const companyId = 1;
    mockAuthService.hasPermission.and.returnValue(throwError(() => new Error('Permission error')));

    service.getCompanyStatistics(companyId).subscribe({
      next: () => fail('Should have thrown error'),
      error: error => {
        expect(error.message).toBe('Permission error');
      },
    });
  });

  it('should handle HTTP errors for global statistics', () => {
    mockAuthService.isAdmin.and.returnValue(of(true));

    service.getGlobalStatistics().subscribe({
      next: () => fail('Should have failed'),
      error: error => {
        expect(error).toBeTruthy();
      },
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/api/global-statistics`);
    req.flush('Error', { status: 500, statusText: 'Internal Server Error' });
  });

  it('should handle HTTP errors for company statistics', () => {
    const companyId = 1;
    mockAuthService.hasPermission.and.returnValue(of(true));

    service.getCompanyStatistics(companyId).subscribe({
      next: () => fail('Should have failed'),
      error: error => {
        expect(error).toBeTruthy();
      },
    });

    const req = httpMock.expectOne(
      `${environment.apiUrl}/api/global-statistics/company/${companyId}`
    );
    req.flush('Error', { status: 404, statusText: 'Not Found' });
  });

  it('should handle network errors', () => {
    mockAuthService.isAdmin.and.returnValue(of(true));

    service.getGlobalStatistics().subscribe({
      next: () => fail('Should have failed'),
      error: error => {
        expect(error).toBeTruthy();
      },
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/api/global-statistics`);
    req.error(new ProgressEvent('Network error'));
  });

  it('should handle empty global statistics', () => {
    const emptyStats: GlobalStatistics = {
      totalUsers: 0,
      totalQuizzes: 0,
      globalAverageScore: 0,
      totalGamesPlayed: 0,
      topEmployees: [],
      companyStats: [],
      quizPerformance: [],
      groupStats: [],
    };

    mockAuthService.isAdmin.and.returnValue(of(true));

    service.getGlobalStatistics().subscribe(stats => {
      expect(stats).toEqual(emptyStats);
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/api/global-statistics`);
    req.flush(emptyStats);
  });

  it('should handle empty company statistics', () => {
    const companyId = 1;
    const emptyStats: CompanyStatistics = {
      teamScores: [],
      groupScores: {},
    };

    mockAuthService.hasPermission.and.returnValue(of(true));

    service.getCompanyStatistics(companyId).subscribe(stats => {
      expect(stats).toEqual(emptyStats);
    });

    const req = httpMock.expectOne(
      `${environment.apiUrl}/api/global-statistics/company/${companyId}`
    );
    req.flush(emptyStats);
  });
});
