import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { of, throwError } from 'rxjs';

import { UserService } from './user.service';
import { AuthService } from './auth.service';
import { User } from '../models/user.interface';
import { environment } from '../../environments/environment';

describe('UserService', () => {
  let service: UserService;
  let httpMock: HttpTestingController;
  let mockAuthService: jasmine.SpyObj<AuthService>;

  beforeEach(() => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', ['hasPermission']);
    authServiceSpy.hasPermission.and.returnValue(of(true));

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [
        UserService,
        { provide: AuthService, useValue: authServiceSpy },
      ],
    });
    service = TestBed.inject(UserService);
    httpMock = TestBed.inject(HttpTestingController);
    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should get current user', () => {
    const mockUser: User = {
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

    service.setCurrentUser(mockUser);
    expect(service.getCurrentUser()).toEqual(mockUser);
  });

  it('should get user profile', () => {
    const mockUser: User = {
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

    service.getUserProfile().subscribe(user => {
      expect(user).toEqual(mockUser);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    expect(req.request.method).toBe('GET');
    req.flush(mockUser);
  });

  it('should get user profile by id', () => {
    const mockUser: User = {
      id: 2,
      email: 'other@example.com',
      firstName: 'Other',
      lastName: 'User',
      roles: ['ROLE_USER'],
      dateRegistration: '2024-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true,
    };

    service.getUserProfileById(2).subscribe(user => {
      expect(user).toEqual(mockUser);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/2`);
    expect(req.request.method).toBe('GET');
    req.flush(mockUser);
  });

  it('should update user profile', () => {
    const updateData = { firstName: 'Updated' };
    const mockUpdatedUser: User = {
      id: 1,
      email: 'test@example.com',
      firstName: 'Updated',
      lastName: 'User',
      roles: ['ROLE_USER'],
      dateRegistration: '2024-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true,
    };

    service.updateUserProfile(updateData).subscribe(user => {
      expect(user).toEqual(mockUpdatedUser);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile/update`);
    expect(req.request.method).toBe('PUT');
    expect(req.request.body).toEqual(updateData);
    req.flush(mockUpdatedUser);
  });

  it('should get user statistics', () => {
    const mockStats = { totalQuizzes: 10, averageScore: 85 };

    service.getUserStatistics().subscribe(stats => {
      expect(stats).toEqual(mockStats);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/statistics`);
    expect(req.request.method).toBe('GET');
    req.flush(mockStats);
  });

  it('should get user statistics by id', () => {
    const mockStats = { totalQuizzes: 5, averageScore: 90 };

    service.getUserStatisticsById(2).subscribe(stats => {
      expect(stats).toEqual(mockStats);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/2/statistics`);
    expect(req.request.method).toBe('GET');
    req.flush(mockStats);
  });

  it('should update avatar', () => {
    const avatarData = { shape: 'blob_circle', color: '#FF0000' };
    const mockUpdatedUser: User = {
      id: 1,
      email: 'test@example.com',
      firstName: 'Test',
      lastName: 'User',
      roles: ['ROLE_USER'],
      dateRegistration: '2024-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true,
      avatarShape: 'blob_circle',
      avatarColor: '#FF0000',
    };

    service.updateAvatar(avatarData).subscribe(user => {
      expect(user).toEqual(mockUpdatedUser);
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile/update`);
    expect(req.request.method).toBe('PUT');
    expect(req.request.body).toEqual({
      avatarShape: 'blob_circle',
      avatarColor: '#FF0000',
    });
    req.flush(mockUpdatedUser);
  });

  it('should handle error when getting user profile', () => {
    service.getUserProfile().subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error.status).toBe(500);
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/user/profile`);
    req.flush('Server error', { status: 500, statusText: 'Internal Server Error' });
  });
});
