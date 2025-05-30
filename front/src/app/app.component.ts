import { Component } from '@angular/core';
import {ActivatedRoute, NavigationEnd, Router, RouterOutlet} from '@angular/router';
import { TuiRoot } from '@taiga-ui/core';
import { NavbarComponent } from './components/navbar/navbar.component';
import {NgIf} from '@angular/common';
import {filter} from 'rxjs/operators';


@Component({
  standalone: true,
  selector: 'app-root',
  imports: [TuiRoot, RouterOutlet, NavbarComponent, NgIf],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss'],
})
export class AppComponent {
  title = 'blob-front';
  showNavbar = true;
  constructor(
    private readonly router: Router,
    private readonly route: ActivatedRoute,
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
