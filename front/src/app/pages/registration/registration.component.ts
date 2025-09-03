import { Component, OnInit, Inject, PLATFORM_ID } from '@angular/core';
import {
  FormBuilder,
  FormGroup,
  Validators,
  ReactiveFormsModule,
  AbstractControl
} from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { NgIf } from '@angular/common';
import { SlideButtonComponent } from '../../components/slide-button/slide-button.component';
import { PasswordInputComponent } from '../../components/password-input/password-input.component';
import { PasswordStrengthIndicatorComponent } from '../../components/password-strength-indicator/password-strength-indicator.component';
import { isPlatformBrowser } from '@angular/common';
import {SeoService} from '../../services/seo.service';
import { AnalyticsService } from '../../services/analytics.service';
import { recaptchaConfig } from '../../../environments/recaptcha';

@Component({
  standalone: true,
  selector: 'app-registration',
  imports: [
    ReactiveFormsModule,
    NgIf,
    RouterLink,
    SlideButtonComponent,
    PasswordInputComponent,
    PasswordStrengthIndicatorComponent
  ],
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
    private readonly seoService: SeoService,
    private readonly analytics: AnalyticsService,
    @Inject(PLATFORM_ID) private platformId: Object
  ) {
    this.form = this.fb.group({
      firstName: ['', [Validators.required]],
      lastName:  ['', [Validators.required]],
      email:     ['', [Validators.required, Validators.email]],
      password:  ['', [Validators.required, Validators.minLength(8), this.passwordStrengthValidator()]],
      confirm:   ['', [Validators.required]],
      tos:       [false, [Validators.requiredTrue]]
    }, { validators: this.passwordsMatch });
  }



  ngOnInit(): void {
    this.seoService.updateSEO({
      title: 'Blob - Inscription',
      description: 'Rejoignez Blob et commencez à créer, partager et jouer à des quiz interactifs éducatifs.',
      keywords: 'inscription, créer un compte, quiz, éducation, formation',
      ogTitle: 'Inscrivez-vous sur Blob',
      ogDescription: 'Créez votre compte Blob pour découvrir une nouvelle façon d’apprendre grâce aux quiz interactifs.',
      ogUrl: '/inscription'
    });
    if (isPlatformBrowser(this.platformId)) {
      this.loadRecaptcha();
    }
  }

  private loadRecaptcha(): void {
    const script = document.createElement('script');
    script.src = `https://www.google.com/recaptcha/api.js?render=${recaptchaConfig.siteKey}`;
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
  }

  private passwordsMatch(group: FormGroup) {
    return group.get('password')!.value === group.get('confirm')!.value
      ? null
      : { mismatch: true };
  }

  private passwordStrengthValidator() {
    return (control: AbstractControl): {[key: string]: any} | null => {
      const password = control.value;
      if (!password) return null;

      const hasUpperCase = /[A-Z]/.test(password);
      const hasLowerCase = /[a-z]/.test(password);
      const hasNumbers = /\d/.test(password);
      const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

      if (!hasUpperCase || !hasLowerCase || !hasNumbers || !hasSpecialChar) {
        return { passwordStrength: true };
      }

      return null;
    };
  }

  onSubmit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.executeRecaptcha().then(token => {
      const { firstName, lastName, email, password } = this.form.value;
      this.auth
        .register(email, password, firstName, lastName, token)
        .subscribe({
          next: () => {
            this.analytics.trackRegistration();
            this.router.navigate(['/connexion']);
          },
          error: () => (this.error = 'Inscription impossible')
        });
    }).catch(error => {
      this.error = 'Erreur de vérification reCAPTCHA';
    });
  }

  private executeRecaptcha(): Promise<string> {
    return new Promise((resolve, reject) => {
      if ((window as any).grecaptcha && (window as any).grecaptcha.ready) {
        (window as any).grecaptcha.ready(() => {
          (window as any).grecaptcha.execute(recaptchaConfig.siteKey, { action: recaptchaConfig.actions.register })
            .then((token: string) => {
              resolve(token);
            })
            .catch((error: any) => {
              reject(error);
            });
        });
      } else {
        reject('reCAPTCHA not loaded');
      }
    });
  }


  showError(fieldName: string): boolean {
    const field = this.form.get(fieldName);
    return field ? field.invalid && field.touched : false;
  }

  showPasswordError(): boolean {
    const password = this.form.get('password');
    return password ? (password.hasError('minlength') || password.hasError('passwordStrength')) && password.touched : false;
  }

  showConfirmError(): boolean {
    const confirm = this.form.get('confirm');
    return this.form.hasError('mismatch') && confirm ? confirm.touched : false;
  }

  getPasswordErrorMessage(): string {
    const password = this.form.get('password');
    if (!password) return '';

    if (password.hasError('minlength')) {
      return 'Le mot de passe doit contenir au moins 8 caractères';
    }

    if (password.hasError('passwordStrength')) {
      return 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial';
    }

    return '';
  }
}
