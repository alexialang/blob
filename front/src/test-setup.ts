import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { ActivatedRoute, Router } from '@angular/router';
import { of } from 'rxjs';
import { AuthService } from './app/services/auth.service';

// Configuration commune pour tous les tests
export function setupTestBed() {
  const mockActivatedRoute = {
    params: of({}),
    queryParams: of({}),
    snapshot: { params: {}, queryParams: {} },
  };

  const mockRouter = {
    navigate: jasmine.createSpy('navigate'),
    events: of({}),
    routerState: { root: { children: [], snapshot: { data: {} } } },
  };

  const mockAuthService = {
    loginStatus$: of(false),
    isLoggedIn: jasmine.createSpy('isLoggedIn').and.returnValue(false),
    hasPermission: jasmine.createSpy('hasPermission').and.returnValue(false),
    hasRole: jasmine.createSpy('hasRole').and.returnValue(false),
    isAdmin: jasmine.createSpy('isAdmin').and.returnValue(false),
    getCurrentUser: jasmine.createSpy('getCurrentUser').and.returnValue(null),
    login: jasmine.createSpy('login').and.returnValue(of({})),
    logout: jasmine.createSpy('logout').and.returnValue(of({})),
  };

  return TestBed.configureTestingModule({
    imports: [HttpClientTestingModule, RouterTestingModule],
    providers: [
      { provide: ActivatedRoute, useValue: mockActivatedRoute },
      { provide: Router, useValue: mockRouter },
      { provide: AuthService, useValue: mockAuthService },
    ],
  });
}
