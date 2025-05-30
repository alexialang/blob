import { Component } from '@angular/core';
import {Router, RouterLink} from '@angular/router';
import { AuthService } from '../../services/auth.service';
import {FormsModule} from '@angular/forms';
import {NgIf} from '@angular/common';
import {SlideButtonComponent} from '../../components/slide-button/slide-button.component';

@Component({
  standalone: true,
  imports: [FormsModule, NgIf, SlideButtonComponent, RouterLink],
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss'],

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
