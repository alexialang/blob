import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { AuthService } from '../../services/auth.service';
import { AlertService } from '../../services/alert.service';
import { SeoService } from '../../services/seo.service';
import { of } from 'rxjs';

import { RegistrationComponent } from './registration.component';

describe('RegistrationComponent', () => {
  let component: RegistrationComponent;
  let fixture: ComponentFixture<RegistrationComponent>;

  beforeEach(async () => {
    const mockAuthService = {
      register: jasmine.createSpy('register').and.returnValue(of({})),
      loginStatus$: of(false),
    };

    const mockAlertService = {
      showSuccess: jasmine.createSpy('showSuccess'),
      showError: jasmine.createSpy('showError'),
    };

    const mockSeoService = {
      setTitle: jasmine.createSpy('setTitle'),
      setDescription: jasmine.createSpy('setDescription'),
      updateSEO: jasmine.createSpy('updateSEO'),
    };

    await TestBed.configureTestingModule({
      imports: [RegistrationComponent, HttpClientTestingModule, RouterTestingModule],
      providers: [
        { provide: AuthService, useValue: mockAuthService },
        { provide: AlertService, useValue: mockAlertService },
        { provide: SeoService, useValue: mockSeoService },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(RegistrationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize correctly', () => {
    expect(component).toBeTruthy();
    // Le composant s'initialise correctement
  });
});
