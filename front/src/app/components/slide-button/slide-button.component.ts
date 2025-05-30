import { Component, Input } from '@angular/core';
import {NgStyle} from '@angular/common';

@Component({
  selector: 'app-slide-button',
  templateUrl: './slide-button.component.html',
  styleUrls: ['./slide-button.component.scss'],
  imports: [
    NgStyle
  ]
})
export class SlideButtonComponent {
  @Input() label = 'Button';
  @Input() duration = 0.4;
  @Input() angle = 5.7;
  @Input() backgroundColor = '#fff';
  @Input() textColor = '#000';
}
