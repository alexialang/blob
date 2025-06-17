import { Component } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import {FormsModule} from '@angular/forms';
import {NgIf} from '@angular/common';
import {environment} from '../../../environments/environment';

@Component({
  selector: 'app-forgot-password',
  templateUrl: './forgot-password.component.html',
  imports: [
    FormsModule,
    NgIf
  ]
})
export class ForgotPasswordComponent {
  email = '';
  message = '';
  error = false;
  loading = false;

  constructor(private http: HttpClient) {}

  onSubmit() {
    this.loading = true;
    this.http.post(`${environment.apiBaseUrl}/forgot-password`, { email: this.email })
      .subscribe({
      next: () => {
        this.message = 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.';
        this.error = false;
        this.loading = false;
      },
      error: () => {
        this.message = 'Une erreur est survenue.';
        this.error = true;
        this.loading = false;
      }
    });
  }
}
