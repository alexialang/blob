import { ComponentFixture, TestBed } from '@angular/core/testing';
import {
  PasswordStrengthIndicatorComponent,
  PasswordStrength,
} from './password-strength-indicator.component';

describe('PasswordStrengthIndicatorComponent', () => {
  let component: PasswordStrengthIndicatorComponent;
  let fixture: ComponentFixture<PasswordStrengthIndicatorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PasswordStrengthIndicatorComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(PasswordStrengthIndicatorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.password).toBe('');
    expect(component.theme).toBe('dark');
  });

  it('should return weak strength for empty password', () => {
    const strength = component.getPasswordStrength();
    expect(strength.score).toBe(0);
    expect(strength.message).toBe('');
    expect(strength.color).toBe('#ccc');
  });

  it('should return weak strength for short password', () => {
    component.password = '123';
    const strength = component.getPasswordStrength();
    expect(strength.score).toBe(1);
    expect(strength.message).toBe('Très faible');
    expect(strength.color).toBe('#ff4444');
  });

  it('should return medium strength for medium password', () => {
    component.password = 'password123';
    const strength = component.getPasswordStrength();
    expect(strength.score).toBe(3);
    expect(strength.message).toBe('Moyen');
    expect(strength.color).toBe('#ffaa00');
  });

  it('should return strong strength for strong password', () => {
    component.password = 'Password123!';
    const strength = component.getPasswordStrength();
    expect(strength.score).toBe(5);
    expect(strength.message).toBe('Très fort');
    expect(strength.color).toBe('#008800');
  });

  it('should emit strength change event', () => {
    spyOn(component.strengthChange, 'emit');
    component.password = 'test123';
    component.getPasswordStrength();
    expect(component.strengthChange.emit).toHaveBeenCalled();
  });
});
