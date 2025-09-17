import { Component, Input, OnInit, ElementRef, ViewChild, AfterViewInit } from '@angular/core';

@Component({
  selector: 'app-optimized-image',
  template: `
    <img
      #imgElement
      [src]="src"
      [alt]="alt"
      [width]="width"
      [height]="height"
      [loading]="loading"
      [decoding]="decoding"
      [class]="cssClass"
      (load)="onImageLoad()"
      (error)="onImageError()"
    />
  `,
  standalone: true,
})
export class OptimizedImageComponent implements OnInit, AfterViewInit {
  @Input() src: string = '';
  @Input() alt: string = '';
  @Input() width: number = 0;
  @Input() height: number = 0;
  @Input() loading: 'lazy' | 'eager' = 'lazy';
  @Input() decoding: 'async' | 'sync' | 'auto' = 'async';
  @Input() cssClass: string = '';

  @ViewChild('imgElement') imgElement!: ElementRef<HTMLImageElement>;

  private observer?: IntersectionObserver;

  ngOnInit() {
    // Validation des attributs requis
    if (!this.src) {
      console.warn('OptimizedImageComponent: src is required');
    }
    if (!this.alt) {
      console.warn('OptimizedImageComponent: alt is required for accessibility');
    }
    if (!this.width || !this.height) {
      console.warn(
        'OptimizedImageComponent: width and height should be specified to prevent layout shift'
      );
    }
  }

  ngAfterViewInit() {
    if (this.loading === 'lazy') {
      this.setupLazyLoading();
    }
  }

  private setupLazyLoading() {
    if (!('IntersectionObserver' in window)) {
      // Fallback pour les navigateurs qui ne supportent pas IntersectionObserver
      this.imgElement.nativeElement.loading = 'lazy';
      return;
    }

    this.observer = new IntersectionObserver(
      entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.loadImage();
            this.observer?.unobserve(entry.target);
          }
        });
      },
      {
        rootMargin: '50px 0px', // Commence à charger 50px avant que l'image soit visible
        threshold: 0.1,
      }
    );

    this.observer.observe(this.imgElement.nativeElement);
  }

  private loadImage() {
    const img = this.imgElement.nativeElement;
    if (img.dataset['src']) {
      img.src = img.dataset['src'];
      delete img.dataset['src'];
    }
  }

  onImageLoad() {
    // Image chargée avec succès
    this.imgElement.nativeElement.classList.add('loaded');
  }

  onImageError() {
    // Gestion d'erreur de chargement d'image
    console.error(`Failed to load image: ${this.src}`);
    this.imgElement.nativeElement.classList.add('error');
  }

  ngOnDestroy() {
    this.observer?.disconnect();
  }
}
