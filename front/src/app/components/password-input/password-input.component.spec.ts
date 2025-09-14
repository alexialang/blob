import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PasswordInputComponent } from './password-input.component';
import { NO_ERRORS_SCHEMA } from '@angular/core';

describe('PasswordInputComponent', () => {
  let component: PasswordInputComponent;
  let fixture: ComponentFixture<PasswordInputComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PasswordInputComponent],
      schemas: [NO_ERRORS_SCHEMA]
    }).compileComponents();

    fixture = TestBed.createComponent(PasswordInputComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.placeholder).toBe('Mot de passe');
    expect(component.required).toBe(false);
    expect(component.disabled).toBe(false);
    expect(component.theme).toBe('dark');
    expect(component.hasError).toBe(false);
  });

  it('should update value when set', () => {
    component.value = 'test123';
    expect(component.value).toBe('test123');
  });

  it('should handle empty value', () => {
    component.value = '';
    expect(component.value).toBe('');
  });

  it('should toggle password visibility', () => {
    expect(component.showPassword).toBe(false);
    component.togglePasswordVisibility();
    expect(component.showPassword).toBe(true);
    component.togglePasswordVisibility();
    expect(component.showPassword).toBe(false);
  });

});