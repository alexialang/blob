import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { of } from 'rxjs';
import { NavbarComponent } from './navbar.component';
import { AuthService } from '../../services/auth.service';
import { DomSanitizer } from '@angular/platform-browser';

describe('NavbarComponent', () => {
  let component: NavbarComponent;
  let fixture: ComponentFixture<NavbarComponent>;
  let mockAuthService: jasmine.SpyObj<AuthService>;
  let mockDomSanitizer: jasmine.SpyObj<DomSanitizer>;

  const mockUser = {
    id: 1,
    email: 'test@example.com',
    firstName: 'Test',
    lastName: 'User',
    pseudo: 'TestUser',
    avatar: 'assets/avatars/blob_circle.svg',
    avatarShape: 'blob_circle',
    avatarColor: '#FF5733',
    companyId: 1,
    roles: ['ROLE_USER'],
    dateRegistration: '2024-01-01',
    isAdmin: false,
    isActive: true,
    isVerified: true,
  };

  beforeEach(async () => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', [
      'isLoggedIn',
      'isGuest',
      'getCurrentUser',
      'isAdmin',
      'logout'
    ], {
      loginStatus$: of(true)
    });

    const sanitizerSpy = jasmine.createSpyObj('DomSanitizer', [
      'bypassSecurityTrustUrl',
      'bypassSecurityTrustHtml'
    ]);

    await TestBed.configureTestingModule({
      imports: [NavbarComponent, HttpClientTestingModule, RouterTestingModule],
      providers: [
        { provide: AuthService, useValue: authServiceSpy },
        { provide: DomSanitizer, useValue: sanitizerSpy }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(NavbarComponent);
    component = fixture.componentInstance;
    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
    mockDomSanitizer = TestBed.inject(DomSanitizer) as jasmine.SpyObj<DomSanitizer>;

    // Configuration des mocks par défaut
    mockAuthService.isLoggedIn.and.returnValue(false);
    mockAuthService.isGuest.and.returnValue(false);
    mockAuthService.getCurrentUser.and.returnValue(of(mockUser));
    mockAuthService.isAdmin.and.returnValue(of(false));
    mockDomSanitizer.bypassSecurityTrustUrl.and.returnValue('sanitized-url' as any);
    mockDomSanitizer.bypassSecurityTrustHtml.and.returnValue('sanitized-html' as any);
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.showGestionDropdown).toBe(false);
    expect(component.showProfileDropdown).toBe(false);
    expect(component.isMobileMenuOpen).toBe(false);
  });

  it('should generate random color on init', () => {
    const initialColor = component.randomColor;
    component.generateRandomColor();
    expect(component.randomColor).toBeDefined();
    expect(component.randomColor).not.toBe(initialColor);
  });

  describe('loadUserData', () => {
    it('should load user data for logged in user', () => {
      mockAuthService.isLoggedIn.and.returnValue(true);
      mockAuthService.isGuest.and.returnValue(false);

      component.loadUserData();

      expect(mockAuthService.getCurrentUser).toHaveBeenCalled();
      expect(mockAuthService.isAdmin).toHaveBeenCalled();
    });

    it('should load guest data when user is guest', () => {
      mockAuthService.isGuest.and.returnValue(true);

      component.loadUserData();

      expect(component.userName$).toBeDefined();
    });

    it('should load default data when user is not logged in and not guest', () => {
      mockAuthService.isLoggedIn.and.returnValue(false);
      mockAuthService.isGuest.and.returnValue(false);

      component.loadUserData();

      expect(component.userName$).toBeDefined();
    });
  });

  describe('avatar conversion methods', () => {
    it('should convert full avatar to head avatar correctly', () => {
      const result = component['getHeadAvatarFromFullAvatar']('assets/avatars/blob_circle.svg');
      expect(result).toBe('assets/avatars/circle_head.svg');
    });

    it('should return guest avatar for empty path', () => {
      const result = component['getHeadAvatarFromFullAvatar']('');
      expect(result).toBe('assets/avatars/head_guest.svg');
    });

    it('should convert shape to head avatar correctly', () => {
      const result = component['getHeadAvatarFromShape']('blob_circle');
      expect(result).toBe('assets/avatars/circle_head.svg');
    });

    it('should return guest avatar for unknown shape', () => {
      const result = component['getHeadAvatarFromShape']('unknown_shape');
      expect(result).toBe('assets/avatars/head_guest.svg');
    });
  });

  describe('logout', () => {
    it('should call authService logout and reload user data', () => {
      spyOn(component, 'loadUserData');
      
      component.logout();
      
      expect(mockAuthService.logout).toHaveBeenCalled();
      expect(component.loadUserData).toHaveBeenCalled();
    });
  });

  describe('mobile menu', () => {
    it('should toggle mobile menu', () => {
      const initialState = component.isMobileMenuOpen;
      component.toggle();
      expect(component.isMobileMenuOpen).toBe(!initialState);
    });

    it('should close mobile menu', () => {
      component.isMobileMenuOpen = true;
      component.close();
      expect(component.isMobileMenuOpen).toBe(false);
    });

    it('should return mobile menu state', () => {
      component.isMobileMenuOpen = true;
      expect(component.open()).toBe(true);
      
      component.isMobileMenuOpen = false;
      expect(component.open()).toBe(false);
    });
  });

  describe('image handling', () => {
    it('should handle image error', () => {
      spyOn(console, 'error');
      const mockEvent = { target: { src: 'invalid-image.jpg' } };
      
      component.onImageError(mockEvent);
      
      expect(console.error).toHaveBeenCalledWith('Image failed to load:', 'invalid-image.jpg');
    });

    it('should handle image load', () => {
      const mockEvent = { target: { src: 'valid-image.jpg' } };
      
      expect(() => component.onImageLoad(mockEvent)).not.toThrow();
    });
  });

  describe('ngOnInit', () => {
    it('should initialize component correctly', () => {
      spyOn(component, 'generateRandomColor');
      spyOn(component, 'loadUserData');
      
      component.ngOnInit();
      
      expect(component.generateRandomColor).toHaveBeenCalled();
      expect(component.loadUserData).toHaveBeenCalled();
    });
  });
});
