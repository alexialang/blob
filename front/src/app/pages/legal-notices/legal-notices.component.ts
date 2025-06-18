import { Component } from '@angular/core';
import {BackButtonComponent} from '../../components/back-button/back-button.component';

@Component({
  standalone: true,
  selector: 'app-legal-notices',
  imports: [
    BackButtonComponent
  ],
  templateUrl: './legal-notices.component.html',
  styleUrl: './legal-notices.component.scss'
})
export class LegalNoticesComponent {

  protected readonly location = location;
}
