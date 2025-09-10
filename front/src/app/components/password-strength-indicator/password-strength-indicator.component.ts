import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

export interface PasswordStrength {
  score: number;
  message: string;
  color: string;
}

@Component({
  selector: 'app-password-strength-indicator',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './password-strength-indicator.component.html',
  styleUrls: ['./password-strength-indicator.component.scss'],
})
export class PasswordStrengthIndicatorComponent {
  @Input() password: string = '';
  @Input() theme: 'light' | 'dark' = 'dark';
  @Output() strengthChange = new EventEmitter<PasswordStrength>();

  getPasswordStrength(): PasswordStrength {
    if (!this.password) {
      const result = { score: 0, message: '', color: '#ccc' };
      this.strengthChange.emit(result);
      return result;
    }

    let score = 0;
    if (this.password.length >= 8) score++;
    if (/[A-Z]/.test(this.password)) score++;
    if (/[a-z]/.test(this.password)) score++;
    if (/\d/.test(this.password)) score++;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(this.password)) score++;

    let message = '';
    let color = '#ccc';

    switch (score) {
      case 0:
      case 1:
        message = 'Très faible';
        color = '#ff4444';
        break;
      case 2:
        message = 'Faible';
        color = '#ff8800';
        break;
      case 3:
        message = 'Moyen';
        color = '#ffaa00';
        break;
      case 4:
        message = 'Bon';
        color = '#00aa00';
        break;
      case 5:
        message = 'Très fort';
        color = '#008800';
        break;
      default:
        message = '';
        color = '#ccc';
    }

    const result = { score, message, color };
    this.strengthChange.emit(result);
    return result;
  }
}
