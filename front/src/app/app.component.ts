import { Component, OnInit } from '@angular/core';
import { RouterOutlet, Router, NavigationEnd } from '@angular/router';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from './components/navbar/navbar.component';
import { GameInvitationToastComponent } from './components/game-invitation-toast/game-invitation-toast.component';
import { QuizTransitionComponent } from './components/quiz-transition/quiz-transition.component';
import { AccessibilityDirective } from './directives/accessibility.directive';
import { filter } from 'rxjs/operators';
import { AuthService } from './services/auth.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, RouterOutlet, NavbarComponent, GameInvitationToastComponent, QuizTransitionComponent, AccessibilityDirective],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent implements OnInit {
  title = 'blob-front';
  showNavbar: boolean = true;

  constructor(private router: Router, private authService: AuthService) {}

  ngOnInit() {
    this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((event: any) => {
      const route = this.router.routerState.root;
      let currentRoute = route;

      while (currentRoute.children.length > 0) {
        currentRoute = currentRoute.children[0];
      }

      const hideNavbar = currentRoute.snapshot.data['hideNavbar'];

      this.showNavbar = !hideNavbar;
    });
  }
}
