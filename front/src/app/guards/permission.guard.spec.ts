import { TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { CanActivateFn } from '@angular/router';
import { of } from 'rxjs';
import { AuthService } from '../services/auth.service';
import { createQuizGuard, manageUsersGuard, viewResultsGuard } from './permission.guard';

describe('Permission Guards', () => {
  let mockAuthService: jasmine.SpyObj<AuthService>;
  let mockRouter: jasmine.SpyObj<Router>;

  beforeEach(() => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', ['hasPermission', 'isLoggedIn']);
    const routerSpy = jasmine.createSpyObj('Router', ['navigate']);

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authServiceSpy },
        { provide: Router, useValue: routerSpy }
      ]
    });

    mockAuthService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
    mockRouter = TestBed.inject(Router) as jasmine.SpyObj<Router>;
    
    // Mock isLoggedIn par défaut
    mockAuthService.isLoggedIn.and.returnValue(true);
  });

  describe('createQuizGuard', () => {
    const executeGuard: CanActivateFn = (...guardParameters) => 
        TestBed.runInInjectionContext(() => createQuizGuard(...guardParameters));

    it('should allow access when user has CREATE_QUIZ permission', () => {
      mockAuthService.hasPermission.and.returnValue(of(true));

      let result: any;
      const guardResult = executeGuard({} as any, {} as any);
      if (typeof guardResult === 'object' && 'subscribe' in guardResult) {
        guardResult.subscribe(res => result = res);
      } else {
        result = guardResult;
      }

      expect(result).toBe(true);
    });

    it('should deny access when user lacks CREATE_QUIZ permission', () => {
      mockAuthService.hasPermission.and.returnValue(of(false));

      let result: any;
      const guardResult = executeGuard({} as any, {} as any);
      if (typeof guardResult === 'object' && 'subscribe' in guardResult) {
        guardResult.subscribe(res => result = res);
      } else {
        result = guardResult;
      }

      expect(result).toBe(false);
      expect(mockRouter.navigate).toHaveBeenCalledWith(['/quiz']);
    });
  });

  describe('manageUsersGuard', () => {
    const executeGuard: CanActivateFn = (...guardParameters) => 
        TestBed.runInInjectionContext(() => manageUsersGuard(...guardParameters));

    it('should allow access when user has MANAGE_USERS permission', () => {
      mockAuthService.hasPermission.and.returnValue(of(true));

      let result: any;
      const guardResult = executeGuard({} as any, {} as any);
      if (typeof guardResult === 'object' && 'subscribe' in guardResult) {
        guardResult.subscribe(res => result = res);
      } else {
        result = guardResult;
      }

      expect(result).toBe(true);
    });
  });

  describe('viewResultsGuard', () => {
    const executeGuard: CanActivateFn = (...guardParameters) => 
        TestBed.runInInjectionContext(() => viewResultsGuard(...guardParameters));

    it('should allow access when user has VIEW_RESULTS permission', () => {
      mockAuthService.hasPermission.and.returnValue(of(true));

      let result: any;
      const guardResult = executeGuard({} as any, {} as any);
      if (typeof guardResult === 'object' && 'subscribe' in guardResult) {
        guardResult.subscribe(res => result = res);
      } else {
        result = guardResult;
      }

      expect(result).toBe(true);
    });
  });
});
