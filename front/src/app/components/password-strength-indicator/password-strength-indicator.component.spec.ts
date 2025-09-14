import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PasswordStrengthIndicatorComponent } from './password-strength-indicator.component';

describe('PasswordStrengthIndicatorComponent', () => {
  let component: PasswordStrengthIndicatorComponent;
  let fixture: ComponentFixture<PasswordStrengthIndicatorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PasswordStrengthIndicatorComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PasswordStrengthIndicatorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should return weak password strength for empty password', () => {
    component.password = '';
    const result = component.getPasswordStrength();
    
    expect(result.score).toBe(0);
    expect(result.message).toBe('');
    expect(result.color).toBe('#ccc');
  });

  it('should return very weak password strength for short password', () => {
    component.password = 'abc';
    const result = component.getPasswordStrength();
    
    expect(result.score).toBe(1);
    expect(result.message).toBe('Très faible');
    expect(result.color).toBe('#ff4444');
  });

  it('should return strong password strength for complex password', () => {
    component.password = 'Abc123!@#';
    const result = component.getPasswordStrength();
    
    expect(result.score).toBe(5);
    expect(result.message).toBe('Très fort');
    expect(result.color).toBe('#008800');
  });

  it('should emit strength change event', () => {
    spyOn(component.strengthChange, 'emit');
    component.password = 'Test123';
    
    component.getPasswordStrength();
    
    expect(component.strengthChange.emit).toHaveBeenCalled();
  });

  it('should set theme input', () => {
    component.theme = 'light';
    expect(component.theme).toBe('light');
  });
});
