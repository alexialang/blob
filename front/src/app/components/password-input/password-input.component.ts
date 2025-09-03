import { Component, Input, Output, EventEmitter, forwardRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ControlValueAccessor, FormsModule, NG_VALUE_ACCESSOR } from '@angular/forms';

@Component({
  selector: 'app-password-input',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './password-input.component.html',
  styleUrls: ['./password-input.component.scss'],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => PasswordInputComponent),
      multi: true
    }
  ]
})
export class PasswordInputComponent implements ControlValueAccessor {
  @Input() placeholder: string = 'Mot de passe';
  @Input() required: boolean = false;
  @Input() disabled: boolean = false;
  @Input() theme: 'light' | 'dark' = 'dark';
  @Input() ariaDescribedBy?: string;
  @Input() hasError: boolean = false;

  @Input() set value(val: string) {
    this._value = val || '';
    this.onChange(this._value);
  }
  get value(): string {
    return this._value;
  }

  @Output() valueChange = new EventEmitter<string>();

  showPassword = false;
  private _value = '';

  private onChange = (value: string) => {};
  private onTouched = () => {};

  togglePasswordVisibility(): void {
    if (!this.disabled) {
      this.showPassword = !this.showPassword;
    }
  }

  onInput(event: Event): void {
    const target = event.target as HTMLInputElement;
    this._value = target.value;
    this.onChange(this._value);
    this.valueChange.emit(this._value);
  }

  onBlur(): void {
    this.onTouched();
  }

  writeValue(value: string): void {
    this._value = value || '';
  }

  registerOnChange(fn: (value: string) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabled = isDisabled;
  }
}
