import { ComponentFixture, TestBed } from '@angular/core/testing';
import { BackButtonComponent } from './back-button.component';
import { Router } from '@angular/router';
import { Location } from '@angular/common';

describe('BackButtonComponent', () => {
  let component: BackButtonComponent;
  let fixture: ComponentFixture<BackButtonComponent>;
  let router: jasmine.SpyObj<Router>;
  let location: jasmine.SpyObj<Location>;

  beforeEach(async () => {
    const routerSpy = jasmine.createSpyObj('Router', ['navigate']);
    const locationSpy = jasmine.createSpyObj('Location', ['back']);

    await TestBed.configureTestingModule({
      imports: [BackButtonComponent],
      providers: [
        { provide: Router, useValue: routerSpy },
        { provide: Location, useValue: locationSpy }
      ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BackButtonComponent);
    component = fixture.componentInstance;
    router = TestBed.inject(Router) as jasmine.SpyObj<Router>;
    location = TestBed.inject(Location) as jasmine.SpyObj<Location>;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default fallback route', () => {
    expect(component.fallbackRoute).toBe('/login');
  });

  it('should go back when history length > 1', () => {
    Object.defineProperty(window, 'history', {
      value: { length: 2 },
      writable: true
    });

    component.goBack();

    expect(location.back).toHaveBeenCalled();
    expect(router.navigate).not.toHaveBeenCalled();
  });

  it('should navigate to fallback route when history length <= 1', () => {
    Object.defineProperty(window, 'history', {
      value: { length: 1 },
      writable: true
    });

    component.goBack();

    expect(location.back).not.toHaveBeenCalled();
    expect(router.navigate).toHaveBeenCalledWith(['/login']);
  });

  it('should use custom fallback route', () => {
    component.fallbackRoute = '/custom-route';
    Object.defineProperty(window, 'history', {
      value: { length: 1 },
      writable: true
    });

    component.goBack();

    expect(router.navigate).toHaveBeenCalledWith(['/custom-route']);
  });
});