import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { CompanyService } from './company.service';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { of } from 'rxjs';

describe('CompanyService', () => {
  let service: CompanyService;
  let httpMock: HttpTestingController;
  let mockAuthService: jasmine.SpyObj<AuthService>;

  beforeEach(() => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', ['hasPermission']);
    authServiceSpy.hasPermission.and.returnValue(of(true));

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [
        CompanyService,
        { provide: AuthService, useValue: authServiceSpy },
      ],
    });
    service = TestBed.inject(CompanyService);
    httpMock = TestBed.inject(HttpTestingController);
    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should get companies', () => {
    const mockCompanies = [
      {
        id: 1,
        name: 'Test Company',
        address: '123 Test St',
        email: 'test@company.com',
        phone: '123-456-7890',
        isActive: true,
      },
    ];

    service.getCompanies().subscribe(companies => {
      expect(companies).toEqual(mockCompanies);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies`);
    expect(req.request.method).toBe('GET');
    req.flush(mockCompanies);
  });

  it('should create company', () => {
    const newCompany = {
      name: 'New Company',
      address: '456 New St',
      email: 'new@company.com',
      phone: '987-654-3210',
    };

    const mockResponse = {
      id: 2,
      ...newCompany,
      isActive: true,
    };

    service.createCompany(newCompany).subscribe(response => {
      expect(response).toEqual(mockResponse);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(newCompany);
    req.flush(mockResponse);
  });

  it('should get company stats', () => {
    const mockStats = {
      id: 1,
      name: 'Test Company',
      userCount: 10,
      activeUsers: 8,
      groupCount: 3,
      quizCount: 5,
      createdAt: '2024-01-01',
      lastActivity: '2024-01-15',
    };

    service.getCompanyStats(1).subscribe(stats => {
      expect(stats).toEqual(mockStats);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies/1/stats`);
    expect(req.request.method).toBe('GET');
    req.flush(mockStats);
  });

  it('should get available users for company', () => {
    const mockUsers = [
      { id: 1, email: 'user1@test.com', firstName: 'John', lastName: 'Doe' },
      { id: 2, email: 'user2@test.com', firstName: 'Jane', lastName: 'Smith' },
    ];

    service.getAvailableUsers(1).subscribe(users => {
      expect(users.length).toBe(2);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies/1/available-users`);
    expect(req.request.method).toBe('GET');
    req.flush(mockUsers);
  });

  it('should handle errors gracefully', () => {
    service.getCompanies().subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error.status).toBe(404);
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/companies`);
    req.flush('Not found', { status: 404, statusText: 'Not Found' });
  });
});
