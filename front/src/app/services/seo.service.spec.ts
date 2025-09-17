import { TestBed } from '@angular/core/testing';
import { SeoService, SEOData } from './seo.service';
import { Meta, Title } from '@angular/platform-browser';
import { DOCUMENT } from '@angular/common';

describe('SeoService', () => {
  let service: SeoService;
  let meta: jasmine.SpyObj<Meta>;
  let title: jasmine.SpyObj<Title>;
  let document: jasmine.SpyObj<Document>;

  beforeEach(() => {
    const metaSpy = jasmine.createSpyObj('Meta', ['updateTag', 'addTag']);
    const titleSpy = jasmine.createSpyObj('Title', ['setTitle']);
    const documentSpy = jasmine.createSpyObj('Document', ['querySelector']);

    TestBed.configureTestingModule({
      providers: [
        SeoService,
        { provide: Meta, useValue: metaSpy },
        { provide: Title, useValue: titleSpy },
        { provide: DOCUMENT, useValue: documentSpy },
      ],
    });

    service = TestBed.inject(SeoService);
    meta = TestBed.inject(Meta) as jasmine.SpyObj<Meta>;
    title = TestBed.inject(Title) as jasmine.SpyObj<Title>;
    document = TestBed.inject(DOCUMENT) as jasmine.SpyObj<Document>;
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should update title', () => {
    const seoData: SEOData = {
      title: 'Test Title',
    };

    service.updateSEO(seoData);

    expect(title.setTitle).toHaveBeenCalledWith('Test Title');
  });

  it('should update meta description', () => {
    const seoData: SEOData = {
      description: 'Test Description',
    };

    service.updateSEO(seoData);

    expect(meta.updateTag).toHaveBeenCalledWith({
      name: 'description',
      content: 'Test Description',
    });
  });

  it('should update meta keywords', () => {
    const seoData: SEOData = {
      keywords: 'test, keywords',
    };

    service.updateSEO(seoData);

    expect(meta.updateTag).toHaveBeenCalledWith({
      name: 'keywords',
      content: 'test, keywords',
    });
  });

  it('should update meta author', () => {
    const seoData: SEOData = {
      author: 'Test Author',
    };

    service.updateSEO(seoData);

    expect(meta.updateTag).toHaveBeenCalledWith({
      name: 'author',
      content: 'Test Author',
    });
  });

  it('should update meta robots', () => {
    const seoData: SEOData = {
      robots: 'noindex, nofollow',
    };

    service.updateSEO(seoData);

    expect(meta.updateTag).toHaveBeenCalledWith({
      name: 'robots',
      content: 'noindex, nofollow',
    });
  });

  it('should update Open Graph tags', () => {
    const seoData: SEOData = {
      ogTitle: 'OG Test Title',
      ogDescription: 'OG Test Description',
      ogImage: '/test-image.jpg',
      ogUrl: '/test-url',
    };

    service.updateSEO(seoData);

    expect(meta.updateTag).toHaveBeenCalledWith({
      property: 'og:title',
      content: 'OG Test Title',
    });
    expect(meta.updateTag).toHaveBeenCalledWith({
      property: 'og:description',
      content: 'OG Test Description',
    });
    expect(meta.updateTag).toHaveBeenCalledWith({
      property: 'og:image',
      content: '/test-image.jpg',
    });
    expect(meta.updateTag).toHaveBeenCalledWith({
      property: 'og:url',
      content: '/test-url',
    });
  });

  it('should use default values when no data provided', () => {
    service.updateSEO({});

    expect(title.setTitle).toHaveBeenCalledWith('Blob.');
    expect(meta.updateTag).toHaveBeenCalledWith({
      name: 'description',
      content:
        "Découvrez Blob, la plateforme de quiz interactifs pour apprendre en s'amusant. Créez, partagez et jouez à des quiz personnalisés.",
    });
  });
});
