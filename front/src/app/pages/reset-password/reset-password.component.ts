import { Component } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgIf } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SlideButtonComponent } from '../../components/slide-button/slide-button.component';
import { BackButtonComponent } from '../../components/back-button/back-button.component';
import { PasswordInputComponent } from '../../components/password-input/password-input.component';
import { PasswordStrengthIndicatorComponent } from '../../components/password-strength-indicator/password-strength-indicator.component';
import { environment } from '../../../environments/environment';

@Component({
  standalone: true,
  imports: [
    FormsModule, 
    NgIf, 
    SlideButtonComponent, 
    BackButtonComponent,
    PasswordInputComponent,
    PasswordStrengthIndicatorComponent
  ],
  selector: 'app-reset-password',
  templateUrl: './reset-password.component.html',
  styleUrls: ['./reset-password.component.scss'],
})
export class ResetPasswordComponent {
  password = '';
  confirmPassword = '';
  error?: string;
  success?: string;
  isLoading = false;
  token: string = '';

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private http: HttpClient
  ) {
    this.token = this.route.snapshot.params['token'] || '';
  }

  onPasswordChange(value: string): void {
    this.password = value;
  }

  onConfirmPasswordChange(value: string): void {
    this.confirmPassword = value;
  }

  validatePassword(password: string): { isValid: boolean; message: string } {
    if (password.length < 8) {
      return { isValid: false, message: 'Le mot de passe doit contenir au moins 8 caractères' };
    }

    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

    if (!hasUpperCase || !hasLowerCase || !hasNumbers || !hasSpecialChar) {
      return { 
        isValid: false, 
        message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial' 
      };
    }

    return { isValid: true, message: '' };
  }

  onReset() {
    this.onSubmit();
  }

  onSubmit() {
    this.error = undefined;
    this.success = undefined;

    if (!this.password || !this.confirmPassword) {
      this.error = 'Veuillez remplir tous les champs';
      return;
    }

    if (this.password !== this.confirmPassword) {
      this.error = 'Les mots de passe ne correspondent pas';
      return;
    }

    const passwordValidation = this.validatePassword(this.password);
    if (!passwordValidation.isValid) {
      this.error = passwordValidation.message;
      return;
    }

    this.isLoading = true;

    this.http.post(`${environment.apiBaseUrl}/reset-password/${this.token}`, {
      password: this.password,
      confirmPassword: this.confirmPassword
    }).subscribe({
      next: (response: any) => {
        this.isLoading = false;
        this.success = 'Mot de passe réinitialisé avec succès !';

        setTimeout(() => {
          this.router.navigate(['/login']);
        }, 2000);
      },
      error: (error) => {
        this.isLoading = false;
        if (error.status === 400) {
          this.error = 'Token invalide ou expiré';
        } else {
          this.error = 'Une erreur est survenue. Veuillez réessayer';
        }
        console.error('Reset password error:', error);
      }
    });
  }
}
