import { TestBed } from '@angular/core/testing';
import { PrivacyAnalyticsService, AnalyticsEvent } from './privacy-analytics.service';

describe('PrivacyAnalyticsService', () => {
  let service: PrivacyAnalyticsService;

  beforeEach(() => {
    // Mock window.umami
    (window as any).umami = {
      track: jasmine.createSpy('track'),
    };

    TestBed.configureTestingModule({});
    service = TestBed.inject(PrivacyAnalyticsService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should handle missing umami gracefully', () => {
    // Remove umami from window
    delete (window as any).umami;

    const event: AnalyticsEvent = {
      name: 'test_event',
      properties: { test: 'value' },
    };

    // Should not throw error
    expect(() => service.trackPageView('/test', 'Test')).not.toThrow();
  });

  it('should not track when analytics is disabled', () => {
    const event: AnalyticsEvent = {
      name: 'test_event',
      properties: { test: 'value' },
    };

    // Disable analytics
    (service as any).isEnabled = false;

    service.trackEvent(event);

    expect((window as any).umami.track).not.toHaveBeenCalled();
  });

  it('should handle umami track error gracefully', () => {
    const event: AnalyticsEvent = {
      name: 'test_event',
      properties: { test: 'value' },
    };

    // Mock umami.track to throw an error
    (window as any).umami.track.and.throwError('Track failed');

    // Should not throw error
    expect(() => service.trackEvent(event)).not.toThrow();
  });
});
