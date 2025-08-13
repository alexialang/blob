import { Component, OnInit, Inject, PLATFORM_ID } from '@angular/core';
import {
  FormBuilder,
  FormGroup,
  Validators,
  ReactiveFormsModule
} from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { NgIf } from '@angular/common';
import {SlideButtonComponent} from '../../components/slide-button/slide-button.component';
import { isPlatformBrowser } from '@angular/common';

@Component({
  standalone: true,
  selector: 'app-registration',
  imports: [ReactiveFormsModule, NgIf, RouterLink, SlideButtonComponent],
  templateUrl: './registration.component.html',
  styleUrls: ['./registration.component.scss']
})
export class RegistrationComponent implements OnInit {
  form: FormGroup;
  error?: string;
  recaptchaToken?: string;

  constructor(
    private readonly fb: FormBuilder,
    private readonly auth: AuthService,
    private readonly router: Router,
    @Inject(PLATFORM_ID) private platformId: Object
  ) {
    this.form = this.fb.group({
      firstName: ['', [Validators.required]],
      lastName:  ['', [Validators.required]],
      email:     ['', [Validators.required, Validators.email]],
      password:  ['', [Validators.required, Validators.minLength(6)]],
      confirm:   ['', [Validators.required]],
      tos:       [false, [Validators.requiredTrue]],
      recaptcha: ['', [Validators.required]]
    }, { validators: this.passwordsMatch });
  }

  ngOnInit(): void {
    if (isPlatformBrowser(this.platformId)) {
      this.loadRecaptcha();
    }
  }

  private loadRecaptcha(): void {
    const script = document.createElement('script');
    script.src = 'https://www.google.com/recaptcha/api.js';
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
    
    (window as any).onCaptchaCallback = (token: string) => {
      this.onCaptchaResolved(token);
    };
  }

  private passwordsMatch(group: FormGroup) {
    return group.get('password')!.value === group.get('confirm')!.value
      ? null
      : { mismatch: true };
  }

  onSubmit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const { firstName, lastName, email, password, recaptcha } = this.form.value;
    this.auth
      .register(email, password, firstName, lastName, recaptcha)
      .subscribe({
        next: () => this.router.navigate(['/connexion']),
        error: () => (this.error = 'Inscription impossible')
      });
  }

  onCaptchaResolved(captchaResponse: string | null): void {
    this.form.patchValue({ recaptcha: captchaResponse });
  }
  showError(fieldName: string): boolean {
    const field = this.form.get(fieldName);
    return field ? field.invalid && field.touched : false;
  }

  showPasswordError(): boolean {
    const password = this.form.get('password');
    return password ? password.hasError('minlength') && password.touched : false;
  }

  showConfirmError(): boolean {
    const confirm = this.form.get('confirm');
    return this.form.hasError('mismatch') && confirm ? confirm.touched : false;
  }
}
