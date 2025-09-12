import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { of, throwError } from 'rxjs';

import { AvatarSelectionComponent } from './avatar-selection.component';
import { UserService } from '../../services/user.service';
import { User } from '../../models/user.interface';

describe('AvatarSelectionComponent', () => {
  let component: AvatarSelectionComponent;
  let fixture: ComponentFixture<AvatarSelectionComponent>;
  let mockUserService: jasmine.SpyObj<UserService>;
  let mockRouter: jasmine.SpyObj<Router>;

  beforeEach(async () => {
    const userServiceSpy = jasmine.createSpyObj('UserService', ['getUserProfile', 'updateAvatar']);
    const routerSpy = jasmine.createSpyObj('Router', ['navigate']);

    await TestBed.configureTestingModule({
      imports: [AvatarSelectionComponent, HttpClientTestingModule],
      providers: [
        { provide: UserService, useValue: userServiceSpy },
        { provide: Router, useValue: routerSpy },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AvatarSelectionComponent);
    component = fixture.componentInstance;
    mockUserService = TestBed.inject(UserService) as jasmine.SpyObj<UserService>;
    mockRouter = TestBed.inject(Router) as jasmine.SpyObj<Router>;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.user).toBeNull();
    expect(component.isLoading).toBeFalse();
    expect(component.selectedShapeIndex).toBe(0);
    expect(component.selectedColor).toBe('#257D54');
  });

  it('should load user profile on init', () => {
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
      avatarShape: 'blob_circle',
      avatarColor: '#91DEDA',
    };

    mockUserService.getUserProfile.and.returnValue(of(mockUser));

    component.ngOnInit();

    expect(mockUserService.getUserProfile).toHaveBeenCalled();
    expect(component.user).toEqual(mockUser);
  });

  it('should navigate to profile on error', () => {
    mockUserService.getUserProfile.and.returnValue(throwError('Error'));

    component.ngOnInit();

    expect(mockRouter.navigate).toHaveBeenCalledWith(['/profil']);
  });

  it('should navigate to previous shape', () => {
    component.selectedShapeIndex = 1;
    component.prevShape();
    expect(component.selectedShapeIndex).toBe(0);
  });

  it('should navigate to next shape', () => {
    component.selectedShapeIndex = 0;
    component.nextShape();
    expect(component.selectedShapeIndex).toBe(1);
  });

  it('should select color', () => {
    component.selectColor('#FAA24B');
    expect(component.selectedColor).toBe('#FAA24B');
  });
});
