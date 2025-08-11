import { Component } from '@angular/core';
import {ActivatedRoute, NavigationEnd, Router, RouterOutlet} from '@angular/router';
import { TuiRoot } from '@taiga-ui/core';
import { NavbarComponent } from './components/navbar/navbar.component';

import { QuizTransitionComponent } from './components/quiz-transition/quiz-transition.component';
import { GameInvitationToastComponent } from './components/game-invitation-toast/game-invitation-toast.component';
import {NgIf} from '@angular/common';
import {filter} from 'rxjs/operators';
import { NgChartsModule } from 'ng2-charts';


@Component({
  standalone: true,
  selector: 'app-root',
  imports: [TuiRoot, RouterOutlet, NavbarComponent, QuizTransitionComponent, GameInvitationToastComponent, NgIf, NgChartsModule],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss'],
})
export class AppComponent {
  title = 'blob-front';
  showNavbar = true;
  constructor(
    private readonly router: Router,
    private readonly route: ActivatedRoute
  ) {
    this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => {
        let current = this.route.root;
        while (current.firstChild) {
          current = current.firstChild;
        }
        this.showNavbar = !current.snapshot.data['hideNavbar'];
      });
  }
}
