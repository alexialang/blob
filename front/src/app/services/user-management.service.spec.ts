import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { of } from 'rxjs';
import { UserManagementService } from './user-management.service';
import { AuthService } from './auth.service';
import { environment } from '../../environments/environment';

describe('UserManagementService', () => {
  let service: UserManagementService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [UserManagementService],
    });
    service = TestBed.inject(UserManagementService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should get users', () => {
    const mockUsers = [
      {
        id: 1,
        email: 'user1@example.com',
        firstName: 'John',
        lastName: 'Doe',
        roles: ['ROLE_USER'],
        dateRegistration: '2024-01-01',
        isAdmin: false,
        isActive: true,
        isVerified: true,
      },
    ];

    service.getUsers().subscribe(users => {
      expect(users).toEqual(mockUsers);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/users`);
    expect(req.request.method).toBe('GET');
    req.flush(mockUsers);
  });

  it('should anonymize user', () => {
    // Mock AuthService pour retourner true pour hasPermission
    const mockAuthService = jasmine.createSpyObj('AuthService', ['hasPermission']);
    mockAuthService.hasPermission.and.returnValue(of(true));

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [UserManagementService, { provide: AuthService, useValue: mockAuthService }],
    });

    service = TestBed.inject(UserManagementService);

    service.anonymizeUser(1).subscribe(() => {
      expect(true).toBe(true); // Test passé si pas d'erreur
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/1/anonymize`);
    expect(req.request.method).toBe('PATCH');
    req.flush(null);
  });

  it('should update user roles', () => {
    const mockAuthService = jasmine.createSpyObj('AuthService', ['hasPermission']);
    mockAuthService.hasPermission.and.returnValue(of(true));

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [UserManagementService, { provide: AuthService, useValue: mockAuthService }],
    });

    service = TestBed.inject(UserManagementService);

    const roles = ['ROLE_USER'];
    const permissions = ['MANAGE_QUIZ'];

    service.updateUserRoles(1, roles, permissions).subscribe(() => {
      expect(true).toBe(true);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-permission/user/1`);
    expect(req.request.method).toBe('PUT');
    expect(req.request.body).toEqual({ roles, permissions });
    req.flush(null);
  });

  it('should handle errors gracefully', () => {
    service.getUsers().subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error.status).toBe(500);
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/users`);
    req.flush('Server error', { status: 500, statusText: 'Internal Server Error' });
  });
});
