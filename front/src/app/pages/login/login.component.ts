import { Component } from '@angular/core';
import { Router }    from '@angular/router';
import { AuthService } from '../../services/auth.service';
import {FormsModule} from '@angular/forms';

@Component({
  standalone: true,
  imports: [FormsModule],
  selector: 'app-login',
  templateUrl: './login.component.html',
})
export class LoginComponent {
  email = '';
  password = '';
  error?: string;

  constructor(
    private auth: AuthService,
    private router: Router
  ) {}

  onSubmit() {
    this.error = undefined;
    this.auth.login(this.email, this.password).subscribe({
      next: () => this.router.navigate(['/gestion-utilisateur']),
      error: () => (this.error = 'Identifiants invalides'),
    });
  }
}
