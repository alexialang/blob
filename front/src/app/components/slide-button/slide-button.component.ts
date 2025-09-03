import { Component, Input, Output, EventEmitter } from '@angular/core';
import {NgStyle} from '@angular/common';

@Component({
  standalone: true,
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
  @Input() negative = false;
  @Input() type: 'button' | 'submit' = 'button';
  @Output() buttonClick = new EventEmitter<void>();

  get finalBackgroundColor() {
    return this.negative ? '#000' : this.backgroundColor;
  }

  get finalButtonBackground() {
    return this.negative ? '#fff' : 'transparent';
  }

  get finalBorderColor() {
    return this.negative ? '#000' : '#fff';
  }

  get finalWhiteTextColor() {
    return this.negative ? '#000' : '#fff';
  }

  get finalBlackTextColor() {
    return this.negative ? '#fff' : '#000';
  }

  onClick() {
    this.buttonClick.emit();
  }
}
