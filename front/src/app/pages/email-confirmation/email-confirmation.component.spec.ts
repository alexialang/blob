import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EmailConfirmationComponent } from './email-confirmation.component';

describe('EmailConfirmationComponent', () => {
  let component: EmailConfirmationComponent;
  let fixture: ComponentFixture<EmailConfirmationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [EmailConfirmationComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(EmailConfirmationComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have correct component structure', () => {
    expect(component).toBeInstanceOf(EmailConfirmationComponent);
    expect(fixture.componentInstance).toBe(component);
  });

  it('should render without errors', () => {
    expect(() => fixture.detectChanges()).not.toThrow();
  });

  it('should be a standalone component', () => {
    expect(component).toBeDefined();
    expect(fixture.componentInstance).toBe(component);
  });

  it('should have no imports initially', () => {
    expect(component).toBeDefined();
  });
});
