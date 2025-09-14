import { ComponentFixture, TestBed } from '@angular/core/testing';
import { BackButtonComponent } from './back-button.component';
import { RouterTestingModule } from '@angular/router/testing';

describe('BackButtonComponent', () => {
  let component: BackButtonComponent;
  let fixture: ComponentFixture<BackButtonComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [BackButtonComponent, RouterTestingModule],
    }).compileComponents();

    fixture = TestBed.createComponent(BackButtonComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.fallbackRoute).toBe('/login');
  });
});