import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PasswordInputComponent } from './password-input.component';

describe('PasswordInputComponent', () => {
  let component: PasswordInputComponent;
  let fixture: ComponentFixture<PasswordInputComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PasswordInputComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(PasswordInputComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.placeholder).toBe('Mot de passe');
    expect(component.required).toBe(false);
    expect(component.disabled).toBe(false);
    expect(component.theme).toBe('dark');
    expect(component.hasError).toBe(false);
  });

  it('should toggle password visibility', () => {
    expect(component.showPassword).toBe(false);
    component.togglePasswordVisibility();
    expect(component.showPassword).toBe(true);
    component.togglePasswordVisibility();
    expect(component.showPassword).toBe(false);
  });

  it('should set value', () => {
    const testValue = 'test123';
    component.value = testValue;
    expect(component.value).toBe(testValue);
  });

  it('should handle input change', () => {
    const testValue = 'newpassword';
    component.value = testValue;
    expect(component.value).toBe(testValue);
  });

  it('should handle blur event', () => {
    component.onBlur();
    expect(component).toBeTruthy();
  });

  it('should implement ControlValueAccessor', () => {
    const testValue = 'test';
    component.writeValue(testValue);
    expect(component.value).toBe(testValue);
  });
});
