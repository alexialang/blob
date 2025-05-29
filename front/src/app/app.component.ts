import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { TuiRoot } from '@taiga-ui/core';
import { NavbarComponent } from './components/navbar/navbar.component';


@Component({
  standalone: true,
  selector: 'app-root',
  imports: [TuiRoot, RouterOutlet, NavbarComponent],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss'],
})
export class AppComponent {
  title = 'blob-front';
}
