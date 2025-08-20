import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../services/auth.service';
import { Observable, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { DomSanitizer, SafeUrl } from '@angular/platform-browser';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [RouterLink, CommonModule],
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit {
  userName$: Observable<string> = of('');
  userAvatar$: Observable<SafeUrl> = of('./assets/svg/logo.svg');
  isGuest$: Observable<boolean> = of(true);
  showGestionDropdown: boolean = false;
  showProfileDropdown: boolean = false;
  isMobileMenuOpen: boolean = false;

  canCreateQuiz$: Observable<boolean> = of(false);
  canManageUsers$: Observable<boolean> = of(false);
  isAdmin$: Observable<boolean> = of(false);
  canViewResults$: Observable<boolean> = of(false);

  randomColor: string = '#0B0C1E';

  constructor(
    private authService: AuthService,
    private sanitizer: DomSanitizer
  ) {
  }

  ngOnInit() {
    this.generateRandomColor();
    this.loadUserData();

    this.authService.loginStatus$.subscribe(() => {
      this.loadUserData();
    });
  }

  generateRandomColor() {
    const colors = ['var(--color-primary)', 'var(--color-secondary)', 'var(--color-accent)', 'var(--color-pink)'];
    this.randomColor = colors[Math.floor(Math.random() * colors.length)];
  }

  loadUserData() {
    this.isGuest$ = of(this.authService.isGuest());

    if (this.authService.isLoggedIn()) {
      this.userName$ = this.authService.getCurrentUser().pipe(
        map(user => {
          return user.pseudo || user.firstName || user.email || 'Utilisateur';
        }),
        catchError(() => of('Utilisateur'))
      );

      this.userAvatar$ = this.authService.getCurrentUser().pipe(
        map(user => {
          if (user.avatar) {
            return this.sanitizer.bypassSecurityTrustUrl(user.avatar);
          }
          const avatarPath = `./assets/avatars/blob_${user.avatarShape || 'circle'}.svg`;
          return this.sanitizer.bypassSecurityTrustUrl(avatarPath);
        }),
        catchError(() => of(this.sanitizer.bypassSecurityTrustUrl('./assets/svg/logo.svg')))
      );

      this.canCreateQuiz$ = this.authService.hasPermission('CREATE_QUIZ');
      this.canManageUsers$ = this.authService.hasPermission('MANAGE_USERS');
      this.isAdmin$ = this.authService.isAdmin();
      this.canViewResults$ = this.authService.hasPermission('VIEW_RESULTS');
    } else if (this.authService.isGuest()) {
      this.userName$ = of('Invité');
      this.userAvatar$ = of(this.sanitizer.bypassSecurityTrustUrl('./assets/avatars/head_guest.svg'));
      this.canCreateQuiz$ = of(false);
      this.canManageUsers$ = of(false);
      this.isAdmin$ = of(false);
      this.canViewResults$ = of(false);
    } else {
      this.userName$ = of('Utilisateur');
      this.userAvatar$ = of(this.sanitizer.bypassSecurityTrustUrl('./assets/svg/logo.svg'));
      this.canCreateQuiz$ = of(false);
      this.canManageUsers$ = of(false);
      this.isAdmin$ = of(false);
      this.canViewResults$ = of(false);
    }
  }

  logout() {
    this.authService.logout();
    this.loadUserData();
  }

  toggle() {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  close() {
    this.isMobileMenuOpen = false;
  }

  open() {
    return this.isMobileMenuOpen;
  }
}

