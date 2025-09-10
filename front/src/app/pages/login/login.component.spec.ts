import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { Router } from '@angular/router';
import { ActivatedRoute } from '@angular/router';
import { of, throwError } from 'rxjs';

import { LoginComponent } from './login.component';
import { AuthService } from '../../services/auth.service';
import { AlertService } from '../../services/alert.service';
import { SeoService } from '../../services/seo.service';

describe('LoginComponent', () => {
  let component: LoginComponent;
  let fixture: ComponentFixture<LoginComponent>;
  let mockAuthService: jasmine.SpyObj<AuthService>;
  let mockRouter: jasmine.SpyObj<Router>;

  beforeEach(async () => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', ['login', 'setGuestMode']);
    const routerSpy = jasmine.createSpyObj('Router', ['navigate']);
    const alertServiceSpy = jasmine.createSpyObj('AlertService', ['error']);
    const seoServiceSpy = jasmine.createSpyObj('SeoService', ['updateSEO']);
    const activatedRouteSpy = jasmine.createSpyObj('ActivatedRoute', [], {
      snapshot: { paramMap: { get: () => null } },
    });

    await TestBed.configureTestingModule({
      imports: [LoginComponent, HttpClientTestingModule],
      providers: [
        { provide: AuthService, useValue: authServiceSpy },
        { provide: Router, useValue: routerSpy },
        { provide: AlertService, useValue: alertServiceSpy },
        { provide: SeoService, useValue: seoServiceSpy },
        { provide: ActivatedRoute, useValue: activatedRouteSpy },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(LoginComponent);
    component = fixture.componentInstance;
    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
    mockRouter = TestBed.inject(Router) as jasmine.SpyObj<Router>;

    // Ne pas appeler detectChanges pour éviter ngOnInit
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with empty email and password', () => {
    expect(component.email).toBe('');
    expect(component.password).toBe('');
    expect(component.error).toBeUndefined();
  });

  it('should call AuthService.login on form submission', () => {
    mockAuthService.login.and.returnValue(of(void 0));

    component.email = 'test@example.com';
    component.password = 'password123';

    component.onSubmit();

    expect(mockAuthService.login).toHaveBeenCalledWith('test@example.com', 'password123');
  });

  it('should handle login errors with 401 status', () => {
    const errorResponse = { status: 401, error: { message: 'Invalid credentials' } };
    mockAuthService.login.and.returnValue(throwError(() => errorResponse));

    component.email = 'test@example.com';
    component.password = 'wrongpassword';

    component.onSubmit();

    expect(component.error).toBe('Identifiants invalides ou compte non vérifié');
  });

  it('should handle rate limiting errors with 429 status', () => {
    const errorResponse = { status: 429, error: { message: 'Too many attempts' } };
    mockAuthService.login.and.returnValue(throwError(() => errorResponse));

    component.email = 'test@example.com';
    component.password = 'password123';

    component.onSubmit();

    expect(component.error).toBe('Too many attempts');
  });

  it('should set guest mode and navigate on guest login', () => {
    component.continueAsGuest();

    expect(mockAuthService.setGuestMode).toHaveBeenCalled();
    expect(mockRouter.navigate).toHaveBeenCalledWith(['/quiz']);
  });

  it('should navigate to registration', () => {
    component.goToRegister();

    expect(mockRouter.navigate).toHaveBeenCalledWith(['/inscription']);
  });
});
