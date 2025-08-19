import { TestBed } from '@angular/core/testing';
import { AuthInterceptor } from './auth.interceptor';
import { AuthService } from '../services/auth.service';

describe('AuthInterceptor', () => {
  let interceptor: AuthInterceptor;
  let authService: any;

  beforeEach(() => {
    const authServiceSpy = {
      getToken: jasmine.createSpy('getToken'),
      refresh: jasmine.createSpy('refresh'),
      logout: jasmine.createSpy('logout')
    };

    TestBed.configureTestingModule({
      providers: [
        AuthInterceptor,
        { provide: AuthService, useValue: authServiceSpy }
      ]
    });

    interceptor = TestBed.inject(AuthInterceptor);
    authService = TestBed.inject(AuthService);
  });

  it('should be created', () => {
    expect(interceptor).toBeTruthy();
  });
});
