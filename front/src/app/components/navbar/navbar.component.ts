import {
  ChangeDetectionStrategy,
  Component, OnInit,
  signal
} from '@angular/core';
import {
  TuiButton,
  TuiLink,
  TuiPopup,
} from '@taiga-ui/core';
import {
  TuiAvatar,
  TuiDrawer, TuiProgressBar,
} from '@taiga-ui/kit';
import {TuiIcon} from '@taiga-ui/core/components/icon';
import {RouterModule, Router} from '@angular/router';
import {AuthService} from '../../services/auth.service';


@Component({
  selector: 'navbar',
  standalone: true,
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    TuiButton,
    TuiLink,
    TuiDrawer,
    TuiPopup,
    TuiAvatar,
    TuiProgressBar,
    TuiIcon,
    RouterModule,

  ],
})
export class NavbarComponent implements OnInit {
  protected readonly open = signal(false);
  showGestionDropdown = false;
  showProfileDropdown = false;
  userName = 'Chargement...';

  constructor(
    private readonly authService: AuthService,
    private readonly router: Router
  ) {}

  toggle(): void {
    this.open.set(!this.open());
  }

  close(): void {
    this.open.set(false);
  }

  randomColor: string = '';

  ngOnInit(): void {
    this.randomColor = this.getRandomColor();
    const cachedName = localStorage.getItem('USER_NAME');
    if (cachedName) {
      this.userName = cachedName;
    }
    this.loadUserData();
  }

  loadUserData(): void {
    if (this.authService.isLoggedIn()) {
      this.authService.getCurrentUser().subscribe({
        next: (user) => {
          const fullName = user.pseudo ?? `${user.firstName} ${user.lastName}`.trim() ?? 'Utilisateur';
          this.userName = fullName;
          localStorage.setItem('USER_NAME', fullName);
        },
        error: (error) => {
          this.userName = 'Utilisateur connecté';
        }
      });
    } else {
      this.userName = 'Utilisateur';
      localStorage.removeItem('USER_NAME');
    }
  }

  logout(): void {
    this.authService.logout();
    localStorage.removeItem('USER_NAME');
    this.router.navigate(['/connexion']);
  }

  getRandomColor(): string {
    const colors = ['#257D54', '#FAA24B', '#D30D4C'];
    return colors[Math.floor(Math.random() * colors.length)];
  }

}

