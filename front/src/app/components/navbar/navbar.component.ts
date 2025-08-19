import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../services/auth.service';
import { Observable, of } from 'rxjs';
import { map, switchMap, catchError } from 'rxjs/operators';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [RouterLink, CommonModule],
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit {
  userName$: Observable<string> = of('');
  userAvatar$: Observable<string> = of('./assets/svg/logo.svg');
  isGuest$: Observable<boolean> = of(true);
  showGestionDropdown: boolean = false;
  showProfileDropdown: boolean = false;
  isMobileMenuOpen: boolean = false;

  canCreateQuiz$: Observable<boolean> = of(false);
  canManageUsers$: Observable<boolean> = of(false);
  isAdmin$: Observable<boolean> = of(false);
  canViewResults$: Observable<boolean> = of(false);

  randomColor: string = '#0B0C1E';

  constructor(private authService: AuthService) {
  }

  ngOnInit() {
    this.generateRandomColor();
    this.loadUserData();
  }

  generateRandomColor() {
    const colors = ['#0B0C1E', '#257D54', '#91DEDA', '#FAA24B', '#D30D4C'];
    this.randomColor = colors[Math.floor(Math.random() * colors.length)];
  }

  loadUserData() {
    this.isGuest$ = of(this.authService.isGuest());

    this.userName$ = this.authService.getCurrentUser().pipe(
      map(user => {
        // Priorité au pseudo, puis au nom complet
        return user.pseudo || user.firstName || user.email || 'Utilisateur';
      }),
      catchError(() => of('Utilisateur'))
    );

    this.userAvatar$ = this.authService.getCurrentUser().pipe(
      map(user => {
        if (user.avatar) return user.avatar;
        return `./assets/avatars/blob_${user.avatarShape || 'circle'}.svg`;
      }),
      catchError(() => of('./assets/svg/logo.svg'))
    );

    this.canCreateQuiz$ = this.authService.hasPermission('CREATE_QUIZ');
    this.canManageUsers$ = this.authService.hasPermission('MANAGE_USERS');
    this.isAdmin$ = this.authService.isAdmin();
    this.canViewResults$ = this.authService.hasPermission('VIEW_RESULTS');
  }

  logout() {
    this.authService.logout();
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

