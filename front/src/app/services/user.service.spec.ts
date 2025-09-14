import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { of } from 'rxjs';

import { UserService } from './user.service';
import { AuthService } from './auth.service';
import { User } from '../models/user.interface';

describe('UserService', () => {
  let service: UserService;
  let httpMock: HttpTestingController;
  let mockAuthService: jasmine.SpyObj<AuthService>;

  beforeEach(() => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', ['hasPermission']);

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [
        UserService,
        { provide: AuthService, useValue: authServiceSpy }
      ]
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
      pseudo: 'testuser',
      avatar: 'avatar.jpg',
      avatarShape: 'circle',
      roles: ['ROLE_USER'],
      userPermissions: [],
      dateRegistration: '2023-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true
    };

    service.setCurrentUser(mockUser);
    expect(service.getCurrentUser()).toEqual(mockUser);
  });

  it('should set current user', () => {
    const mockUser: User = {
      id: 1,
      email: 'test@example.com',
      firstName: 'Test',
      lastName: 'User',
      pseudo: 'testuser',
      avatar: 'avatar.jpg',
      avatarShape: 'circle',
      roles: ['ROLE_USER'],
      userPermissions: [],
      dateRegistration: '2023-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true
    };

    service.setCurrentUser(mockUser);
    service.currentUser$.subscribe(user => {
      expect(user).toEqual(mockUser);
    });
  });

  it('should get user profile', () => {
    const mockUser: User = {
      id: 1,
      email: 'test@example.com',
      firstName: 'Test',
      lastName: 'User',
      pseudo: 'testuser',
      avatar: 'avatar.jpg',
      avatarShape: 'circle',
      roles: ['ROLE_USER'],
      userPermissions: [],
      dateRegistration: '2023-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true
    };

    service.getUserProfile().subscribe(user => {
      expect(user).toEqual(mockUser);
    });

    const req = httpMock.expectOne(`${service['baseUrl']}/profile`);
    expect(req.request.method).toBe('GET');
    req.flush(mockUser);
  });

  it('should get user profile by id with permission', () => {
    const mockUser: User = {
      id: 2,
      email: 'other@example.com',
      firstName: 'Other',
      lastName: 'User',
      pseudo: 'otheruser',
      avatar: 'avatar2.jpg',
      avatarShape: 'circle',
      roles: ['ROLE_USER'],
      userPermissions: [],
      dateRegistration: '2023-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true
    };

    mockAuthService.hasPermission.and.returnValue(of(true));

    service.getUserProfileById(2).subscribe(user => {
      expect(user).toEqual(mockUser);
    });

    const req = httpMock.expectOne(`${service['baseUrl']}/2`);
    expect(req.request.method).toBe('GET');
    req.flush(mockUser);
  });

  it('should throw error when getting user profile by id without permission', () => {
    mockAuthService.hasPermission.and.returnValue(of(false));

    service.getUserProfileById(2).subscribe({
      next: () => fail('Should have thrown an error'),
      error: (error) => {
        expect(error.message).toBe('Permission VIEW_RESULTS requise');
      }
    });
  });

  it('should update user profile', () => {
    const updateData = { firstName: 'Updated' };
    const mockUser: User = {
      id: 1,
      email: 'test@example.com',
      firstName: 'Updated',
      lastName: 'User',
      pseudo: 'testuser',
      avatar: 'avatar.jpg',
      avatarShape: 'circle',
      roles: ['ROLE_USER'],
      userPermissions: [],
      dateRegistration: '2023-01-01',
      isAdmin: false,
      isActive: true,
      isVerified: true
    };

    service.updateUserProfile(updateData).subscribe(user => {
      expect(user).toEqual(mockUser);
    });

    const req = httpMock.expectOne(`${service['baseUrl']}/profile/update`);
    expect(req.request.method).toBe('PUT');
    expect(req.request.body).toEqual(updateData);
    req.flush(mockUser);
  });

  it('should have currentUser$ observable', () => {
    expect(service.currentUser$).toBeDefined();
    expect(typeof service.currentUser$.subscribe).toBe('function');
  });

  it('should return null when no current user is set', () => {
    expect(service.getCurrentUser()).toBeNull();
  });
});