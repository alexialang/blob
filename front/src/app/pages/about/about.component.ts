import { Component } from '@angular/core';
import {BackButtonComponent} from '../../components/back-button/back-button.component';
import {RouterLink} from '@angular/router';

@Component({
  standalone: true,
  selector: 'app-about',
  imports: [
    BackButtonComponent,
    RouterLink
  ],
  templateUrl: './about.component.html',
  styleUrl: './about.component.scss'
})
export class AboutComponent {

}
