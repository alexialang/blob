import { TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { CanActivateFn } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { authGuard } from './auth.guard';

describe('authGuard', () => {
  let mockAuthService: jasmine.SpyObj<AuthService>;
  let mockRouter: jasmine.SpyObj<Router>;

  const executeGuard: CanActivateFn = (...guardParameters) =>
    TestBed.runInInjectionContext(() => authGuard(...guardParameters));

  beforeEach(() => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', ['isLoggedIn', 'isGuest']);
    const routerSpy = jasmine.createSpyObj('Router', ['navigate']);

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authServiceSpy },
        { provide: Router, useValue: routerSpy },
      ],
    });

    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
    mockRouter = TestBed.inject(Router) as jasmine.SpyObj<Router>;
  });

  it('should be created', () => {
    expect(executeGuard).toBeTruthy();
  });

  it('should allow access when user is logged in', () => {
    mockAuthService.isLoggedIn.and.returnValue(true);
    mockAuthService.isGuest.and.returnValue(false);

    const result = executeGuard({} as any, {} as any);

    expect(result).toBe(true);
    expect(mockRouter.navigate).not.toHaveBeenCalled();
  });

  it('should allow access when user is guest', () => {
    mockAuthService.isLoggedIn.and.returnValue(false);
    mockAuthService.isGuest.and.returnValue(true);

    const result = executeGuard({} as any, {} as any);

    expect(result).toBe(true);
    expect(mockRouter.navigate).not.toHaveBeenCalled();
  });

  it('should deny access and redirect when user is not authenticated', () => {
    mockAuthService.isLoggedIn.and.returnValue(false);
    mockAuthService.isGuest.and.returnValue(false);

    const result = executeGuard({} as any, {} as any);

    expect(result).toBe(false);
    expect(mockRouter.navigate).toHaveBeenCalledWith(['/connexion']);
  });
});
