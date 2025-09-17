import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { of } from 'rxjs';

import { CompanyService } from './company.service';
import { AuthService } from './auth.service';
import { environment } from '../../environments/environment';

describe('CompanyService', () => {
  let service: CompanyService;
  let httpMock: HttpTestingController;
  let mockAuthService: jasmine.SpyObj<AuthService>;

  beforeEach(() => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', [
      'getToken',
      'getCurrentUser',
      'hasPermission',
    ]);

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [CompanyService, { provide: AuthService, useValue: authServiceSpy }],
    });

    service = TestBed.inject(CompanyService);
    httpMock = TestBed.inject(HttpTestingController);
    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;

    mockAuthService.getToken.and.returnValue('test-token');
    mockAuthService.hasPermission.and.returnValue(of(true));
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should get company by id', () => {
    const companyId = 1;
    const mockCompany = {
      id: 1,
      name: 'Test Company',
      userCount: 10,
      groupCount: 2,
      quizCount: 5,
    };

    service.getCompany(companyId).subscribe(company => {
      expect(company).toEqual(mockCompany);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies/${companyId}`);
    expect(req.request.method).toBe('GET');
    req.flush(mockCompany);
  });

  it('should get company users', () => {
    const companyId = 1;
    const mockUsers = [
      {
        id: 1,
        email: 'user1@example.com',
        firstName: 'User',
        lastName: 'One',
        roles: ['ROLE_USER'],
        isActive: true,
        isVerified: true,
      },
    ];

    service.getCompanyUsers(companyId).subscribe(users => {
      expect(users).toEqual(mockUsers);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies/${companyId}/users`);
    expect(req.request.method).toBe('GET');
    req.flush(mockUsers);
  });

  it('should get company groups', () => {
    const companyId = 1;
    const mockGroups = [
      {
        id: 1,
        name: 'Development Team',
        description: 'Software developers',
        userCount: 5,
        quizCount: 3,
      },
    ];

    service.getCompanyGroups(companyId).subscribe(groups => {
      expect(groups).toEqual(mockGroups);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies/${companyId}/groups`);
    expect(req.request.method).toBe('GET');
    req.flush(mockGroups);
  });

  it('should get company stats', () => {
    const companyId = 1;
    const mockStats = {
      id: 1,
      name: 'Test Company',
      userCount: 10,
      activeUsers: 8,
      groupCount: 2,
      quizCount: 5,
    };

    service.getCompanyStats(companyId).subscribe(stats => {
      expect(stats).toEqual(mockStats);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies/${companyId}/stats`);
    expect(req.request.method).toBe('GET');
    req.flush(mockStats);
  });

  it('should handle API errors', () => {
    const companyId = 1;

    service.getCompany(companyId).subscribe({
      next: () => fail('Should have failed'),
      error: error => {
        expect(error).toBeTruthy();
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies/${companyId}`);
    req.flush('Error', { status: 500, statusText: 'Internal Server Error' });
  });

  it('should handle network errors', () => {
    const companyId = 1;

    service.getCompany(companyId).subscribe({
      next: () => fail('Should have failed'),
      error: error => {
        expect(error).toBeTruthy();
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies/${companyId}`);
    req.error(new ProgressEvent('Network error'));
  });

  it('should handle empty responses', () => {
    const companyId = 1;
    const emptyCompany = null;

    service.getCompany(companyId).subscribe(company => {
      expect(company).toBeNull();
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies/${companyId}`);
    req.flush(emptyCompany);
  });
});
