import { TestBed } from '@angular/core/testing';
import { Meta, Title } from '@angular/platform-browser';
import { DOCUMENT } from '@angular/common';
import { SeoService, SEOData } from './seo.service';

describe('SeoService', () => {
  let service: SeoService;
  let mockMeta: jasmine.SpyObj<Meta>;
  let mockTitle: jasmine.SpyObj<Title>;
  let mockDocument: jasmine.SpyObj<Document>;

  beforeEach(() => {
    const metaSpy = jasmine.createSpyObj('Meta', ['updateTag']);
    const titleSpy = jasmine.createSpyObj('Title', ['setTitle']);
    const documentSpy = jasmine.createSpyObj('Document', ['createElement']);
    
    // Mock pour createElement
    const mockScript = {
      type: '',
      text: '',
      appendChild: jasmine.createSpy('appendChild')
    };
    const mockHead = {
      appendChild: jasmine.createSpy('appendChild')
    };
    documentSpy.createElement.and.returnValue(mockScript);
    documentSpy.head = mockHead;

    TestBed.configureTestingModule({
      providers: [
        SeoService,
        { provide: Meta, useValue: metaSpy },
        { provide: Title, useValue: titleSpy },
        { provide: DOCUMENT, useValue: documentSpy },
      ],
    });

    service = TestBed.inject(SeoService);
    mockMeta = TestBed.inject(Meta) as jasmine.SpyObj<Meta>;
    mockTitle = TestBed.inject(Title) as jasmine.SpyObj<Title>;
    mockDocument = TestBed.inject(DOCUMENT) as jasmine.SpyObj<Document>;
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should update SEO with default values', () => {
    service.updateSEO();

    expect(mockTitle.setTitle).toHaveBeenCalledWith('Blob.');
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'description', content: "Découvrez Blob, la plateforme de quiz interactifs pour apprendre en s'amusant. Créez, partagez et jouez à des quiz personnalisés." });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'keywords', content: 'quiz, apprentissage, éducation, jeux, formation, entreprise' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'author', content: 'Blob' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'robots', content: 'index, follow' });
  });

  it('should update SEO with custom values', () => {
    const customData: SEOData = {
      title: 'Custom Title',
      description: 'Custom description',
      keywords: 'custom, keywords',
      ogTitle: 'Custom OG Title',
      ogDescription: 'Custom OG Description',
      ogUrl: '/custom-url'
    };

    service.updateSEO(customData);

    expect(mockTitle.setTitle).toHaveBeenCalledWith('Custom Title');
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'description', content: 'Custom description' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'keywords', content: 'custom, keywords' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:title', content: 'Custom OG Title' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:description', content: 'Custom OG Description' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:url', content: '/custom-url' });
  });

  it('should update Open Graph meta tags', () => {
    service.updateSEO();

    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:type', content: 'website' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:title', content: 'Blob - Plateforme de Quiz Interactifs' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:description', content: "Découvrez Blob, la plateforme de quiz interactifs pour apprendre en s'amusant." });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:image', content: '/assets/svg/blob_flower.svg' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ property: 'og:url', content: '/' });
  });

  it('should update Twitter meta tags', () => {
    service.updateSEO();

    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'twitter:card', content: 'summary_large_image' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'twitter:title', content: 'Blob.' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'twitter:description', content: "Découvrez Blob, la plateforme de quiz interactifs pour apprendre en s'amusant. Créez, partagez et jouez à des quiz personnalisés." });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'twitter:image', content: '/assets/svg/blob_flower.svg' });
  });

  it('should add structured data', () => {
    const structuredData = {
      '@context': 'https://schema.org',
      '@type': 'WebSite',
      name: 'Blob',
      url: 'https://blob.example.com'
    };

    service.addStructuredData(structuredData);

    expect(mockDocument.createElement).toHaveBeenCalledWith('script');
    expect(mockDocument.head.appendChild).toHaveBeenCalled();
  });

  it('should merge custom data with defaults', () => {
    const partialData: SEOData = {
      title: 'Custom Title',
      description: 'Custom description'
    };

    service.updateSEO(partialData);

    // Vérifier que les valeurs personnalisées sont utilisées
    expect(mockTitle.setTitle).toHaveBeenCalledWith('Custom Title');
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'description', content: 'Custom description' });
    
    // Vérifier que les valeurs par défaut sont utilisées pour les champs non fournis
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'keywords', content: 'quiz, apprentissage, éducation, jeux, formation, entreprise' });
    expect(mockMeta.updateTag).toHaveBeenCalledWith({ name: 'author', content: 'Blob' });
  });

  it('should handle empty SEO data', () => {
    service.updateSEO({});

    expect(mockTitle.setTitle).toHaveBeenCalledWith('Blob.');
    expect(mockMeta.updateTag).toHaveBeenCalledTimes(13); // Tous les tags meta
  });

  it('should handle undefined SEO data', () => {
    service.updateSEO(undefined as any);

    expect(mockTitle.setTitle).toHaveBeenCalledWith('Blob.');
    expect(mockMeta.updateTag).toHaveBeenCalledTimes(13);
  });
});
