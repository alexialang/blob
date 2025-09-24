import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgIf } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SlideButtonComponent } from '../../components/slide-button/slide-button.component';
import { BackButtonComponent } from '../../components/back-button/back-button.component';
import { environment } from '../../../environments/environment';
import { SeoService } from '../../services/seo.service';

@Component({
  standalone: true,
  imports: [FormsModule, NgIf, SlideButtonComponent, BackButtonComponent, RouterLink],
  selector: 'app-forgot-password',
  templateUrl: './forgot-password.component.html',
  styleUrls: ['./forgot-password.component.scss'],
})
export class ForgotPasswordComponent {
  email = '';
  error?: string;
  success?: string;
  isLoading = false;

  constructor(
    private readonly router: Router,
    private readonly http: HttpClient,
    private readonly seoService: SeoService
  ) {}

  ngOnInit() {
    this.seoService.updateSEO({
      title: 'Blob - Mot de passe oublié',
      description:
        'Réinitialisez votre mot de passe pour retrouver l’accès à votre compte Blob et vos quiz.',
      keywords: 'mot de passe oublié, réinitialisation, compte, connexion, quiz',
      ogTitle: 'Réinitialisez votre mot de passe Blob',
      ogDescription:
        'Suivez les étapes pour récupérer l’accès à votre compte Blob et continuer à jouer à vos quiz interactifs.',
      ogUrl: '/mot-de-passe-oublie',
    });
  }
  onReset() {
    this.onSubmit();
  }

  onSubmit() {
    this.error = undefined;
    this.success = undefined;

    if (!this.email) {
      this.error = 'Veuillez saisir votre email';
      return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(this.email)) {
      this.error = 'Veuillez saisir un email valide';
      return;
    }

    this.isLoading = true;

    this.http.post(`${environment.apiBaseUrl}/forgot-password`, { email: this.email }).subscribe({
      next: (response: any) => {
        this.isLoading = false;
        this.success = 'Un email de réinitialisation a été envoyé à votre adresse !';
        this.email = '';
      },
      error: error => {
        this.isLoading = false;
        if (error.status === 404) {
          this.error = 'Aucun compte trouvé avec cette adresse email';
        } else {
          this.error = 'Une erreur est survenue. Veuillez réessayer';
        }
      },
    });
  }
}
