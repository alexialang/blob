import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { NgIf } from '@angular/common';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-confirm-account',
  standalone: true,
  templateUrl: './confirm-account.component.html',
  styleUrls: ['./confirm-account.component.scss'],
  imports: [NgIf, RouterLink],
})
export class ConfirmAccountComponent implements OnInit {
  message = '';
  error = false;
  loading = true;

  constructor(
    private route: ActivatedRoute,
    private http: HttpClient,
    private router: Router
  ) {}

  ngOnInit(): void {
    const token = this.route.snapshot.paramMap.get('token');
    if (token) {
      this.http.get(`${environment.apiBaseUrl}/confirmation-compte/${token}`).subscribe({
        next: () => {
          this.message = ' Compte confirmé. Vous pouvez maintenant vous connecter.';
          this.loading = false;
        },
        error: () => {
          this.message = ' Lien invalide ou déjà utilisé.';
          this.error = true;
          this.loading = false;
        },
      });
    } else {
      this.message = ' Aucun token fourni.';
      this.error = true;
      this.loading = false;
    }
  }
}
