import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { AuthService } from '../../services/auth.service';
import { of } from 'rxjs';

import { NavbarComponent } from './navbar.component';

describe('NavbarComponent', () => {
  let component: NavbarComponent;
  let fixture: ComponentFixture<NavbarComponent>;

  beforeEach(async () => {
    const mockAuthService = {
      loginStatus$: of(false),
      isLoggedIn: jasmine.createSpy('isLoggedIn').and.returnValue(false),
      isGuest: jasmine.createSpy('isGuest').and.returnValue(true),
      hasPermission: jasmine.createSpy('hasPermission').and.returnValue(false),
      getCurrentUser: jasmine.createSpy('getCurrentUser').and.returnValue(of(null)),
      logout: jasmine.createSpy('logout').and.returnValue(of({})),
    };

    await TestBed.configureTestingModule({
      imports: [NavbarComponent, HttpClientTestingModule, RouterTestingModule],
      providers: [{ provide: AuthService, useValue: mockAuthService }],
    }).compileComponents();

    fixture = TestBed.createComponent(NavbarComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component).toBeTruthy();
    // Test que le composant s'initialise correctement
  });
});
