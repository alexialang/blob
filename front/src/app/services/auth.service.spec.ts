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
      providers: [
        AuthService,
        { provide: Router, useValue: routerSpy }
      ]
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
      refresh_token: 'refresh-token'
    };

    service.login('test@example.com', 'password').subscribe(() => {
      expect(localStorage.getItem('JWT_TOKEN')).toBe('jwt-token');
      expect(localStorage.getItem('REFRESH_TOKEN')).toBe('refresh-token');
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/login_check`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      email: 'test@example.com',
      password: 'password'
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
      roles: ['ROLE_USER']
    };

    service.getCurrentUser().subscribe(user => {
      expect(user).toEqual(mockUser);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    expect(req.request.method).toBe('GET');
    req.flush(mockUser);
  });

  it('should return guest user when in guest mode', () => {
    localStorage.setItem('GUEST_MODE', 'true');

    service.getCurrentUser().subscribe(user => {
      expect(user.firstName).toBe('Invité');
      expect(user.roles).toContain('ROLE_GUEST');
    });

    httpMock.expectNone(`${environment.apiBaseUrl}/user/profile`);
  });

  it('should refresh token', () => {
    localStorage.setItem('REFRESH_TOKEN', 'refresh-token');
    
    const mockResponse = {
      token: 'new-jwt-token',
      refresh_token: 'new-refresh-token'
    };

    service.refresh().subscribe(() => {
      expect(localStorage.getItem('JWT_TOKEN')).toBe('new-jwt-token');
      expect(localStorage.getItem('REFRESH_TOKEN')).toBe('new-refresh-token');
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/token/refresh`);
    expect(req.request.method).toBe('POST');
    req.flush(mockResponse);
  });
});