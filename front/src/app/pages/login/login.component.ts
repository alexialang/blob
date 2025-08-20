import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { AlertService } from '../../services/alert.service';
import { FormsModule } from '@angular/forms';
import { NgIf } from '@angular/common';
import { SlideButtonComponent } from '../../components/slide-button/slide-button.component';
import { PasswordInputComponent } from '../../components/password-input/password-input.component';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  standalone: true,
  imports: [FormsModule, NgIf, SlideButtonComponent, RouterLink, PasswordInputComponent],
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss'],
})
export class LoginComponent {
  email = '';
  password = '';
  error?: string;
  isLoading = false;

  constructor(
    private readonly auth: AuthService,
    private readonly alertService: AlertService,
    private readonly router: Router
  ) {}

  onPasswordChange(value: string): void {
    this.password = value;
  }

  onSubmit() {
    this.error = undefined;

    this.auth.login(this.email, this.password).subscribe({
      next: (result) => {
        window.location.href = '/quiz';
      },
      error: (err: HttpErrorResponse) => {
        if (err.status === 429) {
          const message = err.error?.message || 'Trop de tentatives de connexion. Réessayez dans 15 minutes.';
          this.error = message;
          this.alertService.error(message, 8000);
        } else if (err.status === 401) {
          this.error = 'Identifiants invalides ou compte non vérifié';
        } else {
          this.error = 'Une erreur est survenue. Veuillez réessayer.';
        }
      }
    });
  }

  goToRegister() {
    this.router.navigate(['/inscription']);
  }

  continueAsGuest() {
    this.auth.setGuestMode();
    this.router.navigate(['/quiz']);
  }
}
