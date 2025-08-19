import { Directive, ElementRef, HostListener, OnInit } from '@angular/core';

@Directive({
  selector: '[appAccessibility]',
  standalone: true
})
export class AccessibilityDirective implements OnInit {
  private isKeyboardUser = false;

  constructor(private el: ElementRef) {}

  ngOnInit() {
    this.detectKeyboardUser();
  }

  @HostListener('document:keydown', ['$event'])
  onKeyDown(event: any) {
    if (event.key === 'Tab' || event.key === 'ArrowUp' || event.key === 'ArrowDown' ||
        event.key === 'ArrowLeft' || event.key === 'ArrowRight' || event.key === 'Enter' ||
        event.key === 'Escape' || event.key === ' ') {
      this.isKeyboardUser = true;
    }
  }

  @HostListener('document:mousedown')
  onMouseDown() {
    this.isKeyboardUser = false;
  }

  @HostListener('focus')
  onFocus() {
    if (this.isKeyboardUser) {
      this.el.nativeElement.classList.add('focus-visible');
    }
  }

  @HostListener('blur')
  onBlur() {
    this.el.nativeElement.classList.remove('focus-visible');
  }

  private detectKeyboardUser() {
    const hasUsedKeyboard = sessionStorage.getItem('keyboardUser');
    if (hasUsedKeyboard === 'true') {
      this.isKeyboardUser = true;
    }
  }
}
