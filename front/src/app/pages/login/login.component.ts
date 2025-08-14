import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { FormsModule } from '@angular/forms';
import { NgIf } from '@angular/common';
import { SlideButtonComponent } from '../../components/slide-button/slide-button.component';
import { PasswordInputComponent } from '../../components/password-input/password-input.component';

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
      error: (err) => {
        this.error = 'Identifiants invalides ou compte non vérifié';
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
