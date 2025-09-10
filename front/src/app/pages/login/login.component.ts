import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { AlertService } from '../../services/alert.service';
import { FormsModule } from '@angular/forms';
import { NgIf } from '@angular/common';
import { SlideButtonComponent } from '../../components/slide-button/slide-button.component';
import { PasswordInputComponent } from '../../components/password-input/password-input.component';
import { HttpErrorResponse } from '@angular/common/http';
import { SeoService } from '../../services/seo.service';

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
    private readonly router: Router,
    private readonly seoService: SeoService
  ) {}
  ngOnInit(): void {
    this.seoService.updateSEO({
      title: 'Blob - Connexion',
      description:
        'Connectez-vous à votre compte Blob pour accéder à vos quiz personnalisés et suivre votre progression.',
      keywords: 'connexion, login, compte, quiz, éducation, apprentissage',
      ogTitle: 'Connectez-vous à Blob',
      ogDescription:
        'Accédez à votre compte Blob et profitez de vos quiz interactifs personnalisés.',
      ogUrl: '/connexion',
    });
  }
  onPasswordChange(value: string): void {
    this.password = value;
  }

  onSubmit() {
    this.error = undefined;

    this.auth.login(this.email, this.password).subscribe({
      next: result => {
        window.location.href = '/quiz';
      },
      error: (_err: HttpErrorResponse) => {
        if (_err.status === 429) {
          const message =
            _err.error?.message || 'Trop de tentatives de connexion. Réessayez dans 15 minutes.';
          this.error = message;
          this.alertService.error(message, 8000);
        } else if (_err.status === 401) {
          this.error = 'Identifiants invalides ou compte non vérifié';
        } else {
          this.error = 'Une erreur est survenue. Veuillez réessayer.';
        }
      },
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
