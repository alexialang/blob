import { TestBed } from '@angular/core/testing';
import { Meta, Title } from '@angular/platform-browser';
import { DOCUMENT } from '@angular/common';

import { SeoService, SEOData } from './seo.service';

describe('SeoService', () => {
  let service: SeoService;
  let mockMeta: jasmine.SpyObj<Meta>;
  let mockTitle: jasmine.SpyObj<Title>;
  let mockDocument: jasmine.SpyObj<Document>;
  let mockHead: jasmine.SpyObj<HTMLHeadElement>;

  beforeEach(() => {
    const metaSpy = jasmine.createSpyObj('Meta', ['updateTag']);
    const titleSpy = jasmine.createSpyObj('Title', ['setTitle']);
    const headSpy = jasmine.createSpyObj('HTMLHeadElement', ['appendChild']);
    const documentSpy = jasmine.createSpyObj('Document', ['createElement'], {
      head: headSpy
    });

    TestBed.configureTestingModule({
      providers: [
        SeoService,
        { provide: Meta, useValue: metaSpy },
        { provide: Title, useValue: titleSpy },
        { provide: DOCUMENT, useValue: documentSpy }
      ]
    });

    service = TestBed.inject(SeoService);
    mockMeta = TestBed.inject(Meta) as jasmine.SpyObj<Meta>;
    mockTitle = TestBed.inject(Title) as jasmine.SpyObj<Title>;
    mockDocument = TestBed.inject(DOCUMENT) as jasmine.SpyObj<Document>;
    mockHead = headSpy;
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should update SEO with default values when no data provided', () => {
    service.updateSEO();

    expect(mockTitle.setTitle).toHaveBeenCalledWith('Blob.');
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'description', content: "Découvrez Blob, la plateforme de quiz interactifs pour apprendre en s'amusant. Créez, partagez et jouez à des quiz personnalisés." });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'keywords', content: 'quiz, apprentissage, éducation, jeux, formation, entreprise' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'author', content: 'Blob' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'robots', content: 'index, follow' });
  });

  it('should update SEO with custom data', () => {
    const customData: SEOData = {
      title: 'Custom Title',
      description: 'Custom Description',
      keywords: 'custom, keywords'
    };

    service.updateSEO(customData);

    expect(mockTitle.setTitle).toHaveBeenCalledWith('Custom Title');
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'description', content: 'Custom Description' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'keywords', content: 'custom, keywords' });
  });

  it('should update Open Graph meta tags', () => {
    const customData: SEOData = {
      ogTitle: 'Custom OG Title',
      ogDescription: 'Custom OG Description',
      ogImage: '/custom-image.jpg',
      ogUrl: '/custom-url'
    };

    service.updateSEO(customData);

    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:type', content: 'website' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:title', content: 'Custom OG Title' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:description', content: 'Custom OG Description' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:image', content: '/custom-image.jpg' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:url', content: '/custom-url' });
  });

  it('should update Twitter meta tags', () => {
    service.updateSEO();

    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'twitter:card', content: 'summary_large_image' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'twitter:title', content: 'Blob.' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'twitter:description', content: "Découvrez Blob, la plateforme de quiz interactifs pour apprendre en s'amusant. Créez, partagez et jouez à des quiz personnalisés." });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'twitter:image', content: '/assets/svg/blob_flower.svg' });
  });

  it('should add structured data', () => {
    const mockScript = jasmine.createSpyObj('HTMLScriptElement', [], {
      type: '',
      text: ''
    });
    Object.defineProperty(mockScript, 'type', {
      get: () => mockScript._type || '',
      set: (value) => mockScript._type = value,
      enumerable: true,
      configurable: true
    });
    Object.defineProperty(mockScript, 'text', {
      get: () => mockScript._text || '',
      set: (value) => mockScript._text = value,
      enumerable: true,
      configurable: true
    });
    
    mockDocument.createElement.and.returnValue(mockScript);

    const structuredData = {
      '@context': 'https://schema.org',
      '@type': 'WebSite',
      name: 'Blob'
    };

    service.addStructuredData(structuredData);

    expect(mockDocument.createElement).toHaveBeenCalledWith('script');
    expect(mockScript.type).toBe('application/ld+json');
    expect(mockScript.text).toBe(JSON.stringify(structuredData));
    expect(mockHead.appendChild).toHaveBeenCalledWith(mockScript);
  });

  it('should merge custom data with defaults', () => {
    const customData: SEOData = {
      title: 'Custom Title',
      author: 'Custom Author'
    };

    service.updateSEO(customData);

    expect(mockTitle.setTitle).toHaveBeenCalledWith('Custom Title');
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'author', content: 'Custom Author' });
    // Should still use default description
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'description', content: "Découvrez Blob, la plateforme de quiz interactifs pour apprendre en s'amusant. Créez, partagez et jouez à des quiz personnalisés." });
  });
});