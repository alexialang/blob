import { TestBed } from '@angular/core/testing';
import { PrivacyAnalyticsService, AnalyticsEvent } from './privacy-analytics.service';
import { environment } from '../../environments/environment';

describe('PrivacyAnalyticsService', () => {
  let service: PrivacyAnalyticsService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(PrivacyAnalyticsService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should track event successfully', () => {
    const event: AnalyticsEvent = {
      name: 'test_event',
      properties: { test: 'value' }
    };

    // Mock window.umami
    (window as any).umami = {
      track: jasmine.createSpy('track').and.returnValue(Promise.resolve())
    };

    service.trackEvent(event);

    expect((window as any).umami.track).toHaveBeenCalledWith('test_event', { test: 'value' });
  });

  it('should track page view successfully', () => {
    const url = '/test-page';
    const title = 'Test Page';

    // Mock window.umami
    (window as any).umami = {
      track: jasmine.createSpy('track').and.returnValue(Promise.resolve())
    };

    service.trackPageView(url, title);

    expect((window as any).umami.track).toHaveBeenCalledWith('pageview', { url, title });
  });

  it('should handle missing umami gracefully', () => {
    // Remove umami from window
    delete (window as any).umami;

    const event: AnalyticsEvent = {
      name: 'test_event',
      properties: { test: 'value' }
    };

    // Should not throw error
    expect(() => service.trackEvent(event)).not.toThrow();
  });

  it('should handle missing umami for page view gracefully', () => {
    // Remove umami from window
    delete (window as any).umami;

    // Should not throw error
    expect(() => service.trackPageView('/test', 'Test')).not.toThrow();
  });

  it('should track event with empty properties', () => {
    const event: AnalyticsEvent = {
      name: 'test_event',
      properties: {}
    };

    // Mock window.umami
    (window as any).umami = {
      track: jasmine.createSpy('track').and.returnValue(Promise.resolve())
    };

    service.trackEvent(event);

    expect((window as any).umami.track).toHaveBeenCalledWith('test_event', {});
  });

  it('should track page view without title', () => {
    const url = '/test-page';

    // Mock window.umami
    (window as any).umami = {
      track: jasmine.createSpy('track').and.returnValue(Promise.resolve())
    };

    service.trackPageView(url);

    expect((window as any).umami.track).toHaveBeenCalledWith('pageview', { url, title: undefined });
  });

  it('should track multiple events', () => {
    const event1: AnalyticsEvent = {
      name: 'event1',
      properties: { prop1: 'value1' }
    };
    const event2: AnalyticsEvent = {
      name: 'event2',
      properties: { prop2: 'value2' }
    };

    // Mock window.umami
    (window as any).umami = {
      track: jasmine.createSpy('track').and.returnValue(Promise.resolve())
    };

    service.trackEvent(event1);
    service.trackEvent(event2);

    expect((window as any).umami.track).toHaveBeenCalledTimes(2);
    expect((window as any).umami.track).toHaveBeenCalledWith('event1', { prop1: 'value1' });
    expect((window as any).umami.track).toHaveBeenCalledWith('event2', { prop2: 'value2' });
  });

  it('should handle umami track error gracefully', () => {
    const event: AnalyticsEvent = {
      name: 'test_event',
      properties: { test: 'value' }
    };

    // Mock window.umami with error
    (window as any).umami = {
      track: jasmine.createSpy('track').and.returnValue(Promise.reject(new Error('Track failed')))
    };

    // Should not throw error
    expect(() => service.trackEvent(event)).not.toThrow();
  });

  it('should track event with complex properties', () => {
    const event: AnalyticsEvent = {
      name: 'complex_event',
      properties: {
        string: 'test',
        number: 123,
        boolean: true,
        object: { nested: 'value' },
        array: [1, 2, 3]
      }
    };

    // Mock window.umami
    (window as any).umami = {
      track: jasmine.createSpy('track').and.returnValue(Promise.resolve())
    };

    service.trackEvent(event);

    expect((window as any).umami.track).toHaveBeenCalledWith('complex_event', {
      string: 'test',
      number: 123,
      boolean: true,
      object: { nested: 'value' },
      array: [1, 2, 3]
    });
  });

  it('should track page view with special characters in URL', () => {
    const url = '/test-page?param=value&other=123';
    const title = 'Test Page with Special Chars';

    // Mock window.umami
    (window as any).umami = {
      track: jasmine.createSpy('track').and.returnValue(Promise.resolve())
    };

    service.trackPageView(url, title);

    expect((window as any).umami.track).toHaveBeenCalledWith('pageview', { 
      url: '/test-page?param=value&other=123', 
      title: 'Test Page with Special Chars' 
    });
  });
});
