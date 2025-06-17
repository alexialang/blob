import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import {FormsModule} from '@angular/forms';
import {NgIf} from '@angular/common';
import {environment} from '../../../environments/environment';

@Component({
  selector: 'app-reset-password',
  templateUrl: './reset-password.component.html',
  imports: [
    FormsModule,
    NgIf
  ]
})
export class ResetPasswordComponent implements OnInit {
  token = '';
  password = '';
  message = '';
  loading = false;
  error = false;

  constructor(private route: ActivatedRoute, private http: HttpClient) {}

  ngOnInit(): void {
    this.token = this.route.snapshot.paramMap.get('token') ?? '';
  }

  onSubmit() {
    this.loading = true;
    this.http.post(`${environment.apiBaseUrl}/reset-password/${this.token}`, { password: this.password })
      .subscribe({
      next: () => {
        this.message = 'Mot de passe modifié avec succès';
        this.error = false;
        this.loading = false;
      },
      error: () => {
        this.message = 'Erreur lien invalide ou expiré';
        this.error = true;
        this.loading = false;
      }
    });
  }
}
