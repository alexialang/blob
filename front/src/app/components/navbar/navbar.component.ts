import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../services/auth.service';
import { Observable, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { DomSanitizer, SafeUrl } from '@angular/platform-browser';
import { HasPermissionDirective } from '../../directives/has-permission.directive';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [RouterLink, CommonModule, HasPermissionDirective],
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit {
  userName$: Observable<string> = of('');
  userAvatar$: Observable<SafeUrl> = of('./assets/svg/logo.svg');
  userAvatarShape$: Observable<string> = of('circle');
  userAvatarColor$: Observable<string> = of('#257D54');
  isGuest$: Observable<boolean> = of(true);
  showGestionDropdown: boolean = false;
  showProfileDropdown: boolean = false;
  isMobileMenuOpen: boolean = false;

  isAdmin$: Observable<boolean> = of(false);
  userCompanyId$: Observable<number | null> = of(null);

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

      this.userAvatarShape$ = this.authService.getCurrentUser().pipe(
        map(user => user.avatarShape || 'circle'),
        catchError(() => of('circle'))
      );

      this.userAvatarColor$ = this.authService.getCurrentUser().pipe(
        map(user => user.avatarColor || '#257D54'),
        catchError(() => of('#257D54'))
      );

      this.userAvatar$ = this.authService.getCurrentUser().pipe(
        map(user => {
          const headPath = this.getBlobHeadPath(user.avatarShape || 'circle');
          return this.sanitizer.bypassSecurityTrustUrl(headPath);
        }),
        catchError(() => of(this.sanitizer.bypassSecurityTrustUrl('./assets/avatars/head_guest.svg')))
      );

      this.isAdmin$ = this.authService.isAdmin();

      this.userCompanyId$ = this.authService.getCurrentUser().pipe(
        map(user => user.companyId || null),
        catchError(() => of(null))
      );
    } else if (this.authService.isGuest()) {
      this.userName$ = of('Invité');
      this.userAvatarShape$ = of('guest');
      this.userAvatarColor$ = of('#6B7280');
      this.userAvatar$ = of(this.sanitizer.bypassSecurityTrustUrl('./assets/avatars/head_guest.svg'));
      this.isAdmin$ = of(false);
      this.userCompanyId$ = of(null);
    } else {
      this.userName$ = of('Utilisateur');
      this.userAvatarShape$ = of('circle');
      this.userAvatarColor$ = of('#257D54');
      this.userAvatar$ = of(this.sanitizer.bypassSecurityTrustUrl('./assets/avatars/head_guest.svg'));
      this.isAdmin$ = of(false);
      this.userCompanyId$ = of(null);
    }
  }

  private getBlobHeadPath(avatarShape: string): string {
    const headMapping: { [key: string]: string } = {
      'circle': 'circle_head.svg',
      'blob_circle': 'circle_head.svg',
      'flower': 'flower_head.svg',
      'blob_flower': 'flower_head.svg',
      'pic': 'pic_head.svg',
      'blob_pic': 'pic_head.svg',
      'wave': 'wave_head.svg',
      'blob_wave': 'wave_head.svg'
    };

    const headFile = headMapping[avatarShape] || 'circle_head.svg';
    return `./assets/avatars/${headFile}`;
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

