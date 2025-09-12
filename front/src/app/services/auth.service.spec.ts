import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { Router } from '@angular/router';
import { AuthService } from './auth.service';
import { environment } from '../../environments/environment';

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;
  let mockRouter: jasmine.SpyObj<Router>;

  beforeEach(() => {
    const routerSpy = jasmine.createSpyObj('Router', ['navigate']);

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [AuthService, { provide: Router, useValue: routerSpy }],
    });
    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);
    mockRouter = TestBed.inject(Router) as jasmine.SpyObj<Router>;
  });

  afterEach(() => {
    httpMock.verify();
    localStorage.clear();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should login successfully', () => {
    const mockResponse = {
      token: 'jwt-token',
      refresh_token: 'refresh-token',
    };

    service.login('test@example.com', 'password').subscribe(() => {
      expect(localStorage.getItem('JWT_TOKEN')).toBe('jwt-token');
      expect(localStorage.getItem('REFRESH_TOKEN')).toBe('refresh-token');
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/login_check`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      email: 'test@example.com',
      password: 'password',
    });
    req.flush(mockResponse);
  });

  it('should check if user is logged in', () => {
    expect(service.isLoggedIn()).toBe(false);

    localStorage.setItem('JWT_TOKEN', 'test-token');
    expect(service.isLoggedIn()).toBe(true);
  });

  it('should check if user is guest', () => {
    expect(service.isGuest()).toBe(false);

    localStorage.setItem('GUEST_MODE', 'true');
    expect(service.isGuest()).toBe(true);
  });

  it('should logout and clear tokens', () => {
    localStorage.setItem('JWT_TOKEN', 'test-token');
    localStorage.setItem('REFRESH_TOKEN', 'refresh-token');
    localStorage.setItem('GUEST_MODE', 'true');

    service.logout();

    expect(localStorage.getItem('JWT_TOKEN')).toBeNull();
    expect(localStorage.getItem('REFRESH_TOKEN')).toBeNull();
    expect(localStorage.getItem('GUEST_MODE')).toBeNull();
    expect(mockRouter.navigate).toHaveBeenCalledWith(['/connexion']);
  });

  it('should get current user when logged in', () => {
    localStorage.setItem('JWT_TOKEN', 'test-token');

    const mockUser = {
      id: 1,
      email: 'test@example.com',
      firstName: 'Test',
      lastName: 'User',
      roles: ['ROLE_USER'],
      dateRegistration: '2024-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true,
    };

    service.getCurrentUser().subscribe(user => {
      expect(user.id).toBe(mockUser.id);
      expect(user.email).toBe(mockUser.email);
      expect(user.firstName).toBe(mockUser.firstName);
      expect(user.lastName).toBe(mockUser.lastName);
      expect(user.roles).toEqual(mockUser.roles);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    expect(req.request.method).toBe('GET');
    req.flush(mockUser);
  });

  it('should return guest user when in guest mode', () => {
    localStorage.setItem('GUEST_MODE', 'true');

    service.getCurrentUser().subscribe(user => {
      expect(user.firstName).toBe('Invité');
      expect(user.roles).toEqual([]); // L'utilisateur invité n'a pas de rôles
    });

    httpMock.expectNone(`${environment.apiBaseUrl}/user/profile`);
  });

  it('should refresh token', () => {
    localStorage.setItem('REFRESH_TOKEN', 'refresh-token');

    const mockResponse = {
      token: 'new-jwt-token',
      refresh_token: 'new-refresh-token',
    };

    service.refresh().subscribe(() => {
      expect(localStorage.getItem('JWT_TOKEN')).toBe('new-jwt-token');
      expect(localStorage.getItem('REFRESH_TOKEN')).toBe('new-refresh-token');
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/token/refresh`);
    expect(req.request.method).toBe('POST');
    req.flush(mockResponse);
  });

  it('should fail to refresh without refresh token', () => {
    localStorage.removeItem('REFRESH_TOKEN');

    service.refresh().subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error.message).toBe('No refresh token');
      },
    });

    httpMock.expectNone(`${environment.apiBaseUrl}/token/refresh`);
  });

  it('should check if user has role', () => {
    const mockUser = {
      id: 1,
      email: 'admin@example.com',
      firstName: 'Admin',
      lastName: 'User',
      roles: ['ROLE_ADMIN'],
      dateRegistration: '2024-01-01',
      isAdmin: true,
      isActive: true,
      isVerified: true,
    };

    localStorage.setItem('JWT_TOKEN', 'test-token');

    service.hasRole('ROLE_ADMIN').subscribe(hasRole => {
      expect(hasRole).toBe(true);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    req.flush(mockUser);
  });

  it('should check if user has permission', () => {
    const mockUser = {
      id: 1,
      email: 'user@example.com',
      firstName: 'Test',
      lastName: 'User',
      roles: ['ROLE_USER'],
      dateRegistration: '2024-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true,
      userPermissions: [{ id: 1, permission: 'CREATE_QUIZ' }],
    };

    localStorage.setItem('JWT_TOKEN', 'test-token');

    service.hasPermission('CREATE_QUIZ').subscribe(hasPermission => {
      expect(hasPermission).toBe(true);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    req.flush(mockUser);
  });

  it('should return true for admin permissions', () => {
    const mockUser = {
      id: 1,
      email: 'admin@example.com',
      firstName: 'Admin',
      lastName: 'User',
      roles: ['ROLE_ADMIN'],
      dateRegistration: '2024-01-01',
      isAdmin: true,
      isActive: true,
      isVerified: true,
    };

    localStorage.setItem('JWT_TOKEN', 'test-token');

    service.hasPermission('ANY_PERMISSION').subscribe(hasPermission => {
      expect(hasPermission).toBe(true);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    req.flush(mockUser);
  });

  it('should check if user is admin', () => {
    const mockUser = {
      id: 1,
      email: 'admin@example.com',
      firstName: 'Admin',
      lastName: 'User',
      roles: ['ROLE_ADMIN'],
      dateRegistration: '2024-01-01',
      isAdmin: true,
      isActive: true,
      isVerified: true,
    };

    localStorage.setItem('JWT_TOKEN', 'test-token');

    service.isAdmin().subscribe(isAdmin => {
      expect(isAdmin).toBe(true);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    req.flush(mockUser);
  });

  it('should set guest mode', () => {
    service.setGuestMode();
    expect(localStorage.getItem('GUEST_MODE')).toBe('true');
  });

  it('should clear guest mode', () => {
    localStorage.setItem('GUEST_MODE', 'true');
    service.clearGuestMode();
    expect(localStorage.getItem('GUEST_MODE')).toBeNull();
  });

  it('should get token', () => {
    expect(service.getToken()).toBeNull();
    
    localStorage.setItem('JWT_TOKEN', 'test-token');
    expect(service.getToken()).toBe('test-token');
  });

  it('should handle hasAnyPermission for admin', () => {
    const mockUser = {
      id: 1,
      email: 'admin@example.com',
      firstName: 'Admin',
      lastName: 'User',
      roles: ['ROLE_ADMIN'],
      dateRegistration: '2024-01-01',
      isAdmin: true,
      isActive: true,
      isVerified: true,
    };

    localStorage.setItem('JWT_TOKEN', 'test-token');

    service.hasAnyPermission(['CREATE_QUIZ', 'DELETE_QUIZ']).subscribe(hasPermission => {
      expect(hasPermission).toBe(true);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    req.flush(mockUser);
  });

  it('should handle hasAnyPermission for regular user', () => {
    const mockUser = {
      id: 1,
      email: 'user@example.com',
      firstName: 'Test',
      lastName: 'User',
      roles: ['ROLE_USER'],
      dateRegistration: '2024-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true,
      userPermissions: [{ id: 1, permission: 'CREATE_QUIZ' }],
    };

    localStorage.setItem('JWT_TOKEN', 'test-token');

    service.hasAnyPermission(['CREATE_QUIZ', 'DELETE_QUIZ']).subscribe(hasPermission => {
      expect(hasPermission).toBe(true); // A au moins CREATE_QUIZ
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    req.flush(mockUser);
  });

  it('should handle hasAnyPermission for user without permissions', () => {
    const mockUser = {
      id: 1,
      email: 'user@example.com',
      firstName: 'Test',
      lastName: 'User',
      roles: ['ROLE_USER'],
      dateRegistration: '2024-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true,
      userPermissions: [],
    };

    localStorage.setItem('JWT_TOKEN', 'test-token');

    service.hasAnyPermission(['DELETE_QUIZ', 'MANAGE_USERS']).subscribe(hasPermission => {
      expect(hasPermission).toBe(false);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    req.flush(mockUser);
  });

  it('should register user successfully', () => {
    const mockResponse = { message: 'User created successfully' };
    const userData = {
      email: 'test@example.com',
      password: 'password123',
      firstName: 'Test',
      lastName: 'User',
      recaptchaToken: 'recaptcha-token'
    };

    service.register(
      userData.email,
      userData.password,
      userData.firstName,
      userData.lastName,
      userData.recaptchaToken
    ).subscribe(response => {
      expect(response).toEqual(mockResponse);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user-create`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(userData);
    req.flush(mockResponse);
  });

  it('should handle login error', () => {
    service.login('test@example.com', 'wrong-password').subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error).toBeDefined();
      }
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/login_check`);
    req.flush({ error: 'Invalid credentials' }, { status: 401, statusText: 'Unauthorized' });
  });

  it('should handle refresh token error', () => {
    localStorage.setItem('REFRESH_TOKEN', 'invalid-refresh-token');

    service.refresh().subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error).toBeDefined();
      }
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/token/refresh`);
    req.flush({ error: 'Invalid refresh token' }, { status: 401, statusText: 'Unauthorized' });
  });
});
