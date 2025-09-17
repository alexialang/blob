import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { Router, NavigationEnd, ActivatedRoute } from '@angular/router';
import { AppComponent } from './app.component';
import { AuthService } from './services/auth.service';
import { PrivacyAnalyticsService } from './services/privacy-analytics.service';
import { of, Subject } from 'rxjs';

describe('AppComponent', () => {
  let mockRouter: jasmine.SpyObj<Router>;
  let mockAuthService: jasmine.SpyObj<AuthService>;
  let mockAnalyticsService: jasmine.SpyObj<PrivacyAnalyticsService>;
  let navigationSubject: Subject<any>;

  beforeEach(async () => {
    navigationSubject = new Subject();
    mockRouter = jasmine.createSpyObj('Router', ['navigate'], {
      events: navigationSubject.asObservable(),
      routerState: {
        root: {
          children: [],
          snapshot: { data: {} },
        },
      },
    });
    mockAuthService = jasmine.createSpyObj('AuthService', ['loginStatus$', 'isLoggedIn']);
    mockAuthService.loginStatus$ = of(false);
    mockAnalyticsService = jasmine.createSpyObj('PrivacyAnalyticsService', ['trackPageView']);

    await TestBed.configureTestingModule({
      imports: [AppComponent, HttpClientTestingModule],
      providers: [
        { provide: Router, useValue: mockRouter },
        { provide: AuthService, useValue: mockAuthService },
        { provide: PrivacyAnalyticsService, useValue: mockAnalyticsService },
      ],
    }).compileComponents();
  });

  it('should create the app', () => {
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;
    expect(app).toBeTruthy();
  });

  it('should initialize with showNavbar true', () => {
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;
    expect(app.showNavbar).toBe(true);
  });

  it('should show navbar when route does not hide it', () => {
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;

    // Initialize component to set up the subscription
    app.ngOnInit();

    // Simulate navigation event without hideNavbar
    const navigationEnd = new NavigationEnd(1, '/test', '/test');
    navigationSubject.next(navigationEnd);

    expect(app.showNavbar).toBe(true);
  });

  it('should hide navbar when route has hideNavbar data', () => {
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;

    // Initialize component to set up the subscription
    app.ngOnInit();

    // Mock route with hideNavbar data
    const mockRoute = {
      snapshot: { data: { hideNavbar: true } },
      children: [],
    };
    (mockRouter.routerState as any).root = mockRoute;

    // Simulate navigation event
    const navigationEnd = new NavigationEnd(1, '/test', '/test');
    navigationSubject.next(navigationEnd);

    expect(app.showNavbar).toBe(false);
  });

  it('should track page view on navigation', () => {
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;

    // Initialize component to set up the subscription
    app.ngOnInit();

    // Simulate navigation event
    const navigationEnd = new NavigationEnd(1, '/test-page', '/test-page');
    navigationSubject.next(navigationEnd);

    expect(mockAnalyticsService.trackPageView).toHaveBeenCalledWith('/test-page', document.title);
  });

  it('should handle nested routes correctly', () => {
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;

    // Initialize component to set up the subscription
    app.ngOnInit();

    // Mock nested route structure
    const mockChildRoute = {
      snapshot: { data: { hideNavbar: false } },
      children: [],
    };
    const mockParentRoute = {
      snapshot: { data: {} },
      children: [mockChildRoute],
    };
    (mockRouter.routerState as any).root = mockParentRoute;

    // Simulate navigation event
    const navigationEnd = new NavigationEnd(1, '/nested/route', '/nested/route');
    navigationSubject.next(navigationEnd);

    expect(app.showNavbar).toBe(true);
  });
});
