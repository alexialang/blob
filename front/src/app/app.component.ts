import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, Router, NavigationEnd } from '@angular/router';
import { NavbarComponent } from './components/navbar/navbar.component';
import { GameInvitationToastComponent } from './components/game-invitation-toast/game-invitation-toast.component';
import { QuizTransitionComponent } from './components/quiz-transition/quiz-transition.component';
import { AlertComponent } from './components/alert/alert.component';

import { AuthService } from './services/auth.service';
import { PrivacyAnalyticsService } from './services/privacy-analytics.service';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [
    CommonModule,
    RouterOutlet,
    NavbarComponent,
    GameInvitationToastComponent,
    QuizTransitionComponent,
    AlertComponent,
  ],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss'],
})
export class AppComponent implements OnInit {
  showNavbar: boolean = true;

  constructor(
    private router: Router,
    private authService: AuthService,
    private analyticsService: PrivacyAnalyticsService
  ) {}

  ngOnInit() {
    this.router.events
      .pipe(filter(event => event instanceof NavigationEnd))
      .subscribe((event: any) => {
        const route = this.router.routerState.root;
        let currentRoute = route;

        while (currentRoute.children.length > 0) {
          currentRoute = currentRoute.children[0];
        }

        const hideNavbar = currentRoute.snapshot.data['hideNavbar'];
        this.showNavbar = !hideNavbar;

        // Tracking automatique des pages avec Umami
        this.analyticsService.trackPageView(event.url, document.title);
      });
  }
}
