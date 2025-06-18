import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgIf } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SlideButtonComponent } from '../../components/slide-button/slide-button.component';
import { BackButtonComponent } from '../../components/back-button/back-button.component';
import {environment} from '../../../environments/environment';

@Component({
  standalone: true,
  imports: [FormsModule, NgIf, SlideButtonComponent, BackButtonComponent],
  selector: 'app-forgot-password',
  templateUrl: './forgot-password.component.html',
  styleUrls: ['./forgot-password.component.scss'],
})
export class ForgotPasswordComponent {
  email = '';
  error?: string;
  success?: string;
  isLoading = false;

  constructor(private router: Router, private http: HttpClient) {}

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

    this.http.post(`${environment.apiBaseUrl}/forgot-password`, { email: this.email })
      .subscribe({
        next: (response: any) => {
          this.isLoading = false;
          this.success = 'Un email de réinitialisation a été envoyé à votre adresse !';
          this.email = '';
        },
        error: (error) => {
          this.isLoading = false;
          if (error.status === 404) {
            this.error = 'Aucun compte trouvé avec cette adresse email';
          } else {
            this.error = 'Une erreur est survenue. Veuillez réessayer';
          }
        }
      });
  }
}
