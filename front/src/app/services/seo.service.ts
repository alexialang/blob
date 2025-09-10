import { Injectable, Inject } from '@angular/core';
import { Meta, Title } from '@angular/platform-browser';
import { DOCUMENT } from '@angular/common';

export interface SEOData {
  title?: string;
  description?: string;
  keywords?: string;
  author?: string;
  robots?: string;
  ogImage?: string;
  ogUrl?: string;
  ogTitle?: string;
  ogDescription?: string;
}

@Injectable({
  providedIn: 'root',
})
export class SeoService {
  private defaults: Required<SEOData> = {
    title: 'Blob.',
    description:
      "Découvrez Blob, la plateforme de quiz interactifs pour apprendre en s'amusant. Créez, partagez et jouez à des quiz personnalisés.",
    keywords: 'quiz, apprentissage, éducation, jeux, formation, entreprise',
    author: 'Blob',
    robots: 'index, follow',
    ogImage: '/assets/svg/blob_flower.svg',
    ogUrl: '/',
    ogTitle: 'Blob - Plateforme de Quiz Interactifs',
    ogDescription: "Découvrez Blob, la plateforme de quiz interactifs pour apprendre en s'amusant.",
  };

  constructor(
    private meta: Meta,
    private title: Title,
    @Inject(DOCUMENT) private document: Document
  ) {}

  updateSEO(data: SEOData = {}): void {
    const metaData = { ...this.defaults, ...data };

    // Title
    this.title.setTitle(metaData.title);

    // Standard meta
    this.meta.updateTag({ name: 'description', content: metaData.description });
    this.meta.updateTag({ name: 'keywords', content: metaData.keywords });
    this.meta.updateTag({ name: 'author', content: metaData.author });
    this.meta.updateTag({ name: 'robots', content: metaData.robots });

    // Open Graph
    this.meta.updateTag({ property: 'og:type', content: 'website' });
    this.meta.updateTag({ property: 'og:title', content: metaData.ogTitle });
    this.meta.updateTag({ property: 'og:description', content: metaData.ogDescription });
    this.meta.updateTag({ property: 'og:image', content: metaData.ogImage });
    this.meta.updateTag({ property: 'og:url', content: metaData.ogUrl });

    // Twitter
    this.meta.updateTag({ name: 'twitter:card', content: 'summary_large_image' });
    this.meta.updateTag({ name: 'twitter:title', content: metaData.title });
    this.meta.updateTag({ name: 'twitter:description', content: metaData.description });
    this.meta.updateTag({ name: 'twitter:image', content: metaData.ogImage });
  }

  addStructuredData(data: any): void {
    const script = this.document.createElement('script');
    script.type = 'application/ld+json';
    script.text = JSON.stringify(data);
    this.document.head.appendChild(script);
  }
}
