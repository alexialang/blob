import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PrivacyConsentComponent } from './privacy-consent.component';
import { PrivacyAnalyticsService } from '../../services/privacy-analytics.service';

describe('PrivacyConsentComponent', () => {
  let component: PrivacyConsentComponent;
  let fixture: ComponentFixture<PrivacyConsentComponent>;
  let mockPrivacyAnalyticsService: jasmine.SpyObj<PrivacyAnalyticsService>;

  beforeEach(async () => {
    mockPrivacyAnalyticsService = jasmine.createSpyObj('PrivacyAnalyticsService', [
      'initializeAnalytics',
      'trackEvent'
    ]);

    await TestBed.configureTestingModule({
      imports: [PrivacyConsentComponent],
      providers: [
        { provide: PrivacyAnalyticsService, useValue: mockPrivacyAnalyticsService }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(PrivacyConsentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should show consent banner when no consent is given', () => {
    spyOn(localStorage, 'getItem').and.returnValue(null);
    component.ngOnInit();
    expect(component.showConsentBanner).toBe(true);
  });


});