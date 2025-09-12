import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { ActivatedRoute } from '@angular/router';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { of } from 'rxjs';

import { DonationComponent } from './donation.component';
import { DonationService } from '../../services/donation.service';
import { SeoService } from '../../services/seo.service';

describe('DonationComponent', () => {
  let component: DonationComponent;
  let fixture: ComponentFixture<DonationComponent>;
  let mockDonationService: jasmine.SpyObj<DonationService>;
  let mockRouter: jasmine.SpyObj<Router>;
  let mockSeoService: jasmine.SpyObj<SeoService>;

  beforeEach(async () => {
    const donationServiceSpy = jasmine.createSpyObj('DonationService', ['createPaymentLink']);
    const routerSpy = jasmine.createSpyObj('Router', ['navigate']);
    const seoServiceSpy = jasmine.createSpyObj('SeoService', ['updateSEO']);
    const activatedRouteSpy = jasmine.createSpyObj('ActivatedRoute', [], {
      queryParams: of({}),
    });

    await TestBed.configureTestingModule({
      imports: [DonationComponent, HttpClientTestingModule],
      providers: [
        { provide: DonationService, useValue: donationServiceSpy },
        { provide: Router, useValue: routerSpy },
        { provide: SeoService, useValue: seoServiceSpy },
        { provide: ActivatedRoute, useValue: activatedRouteSpy },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(DonationComponent);
    component = fixture.componentInstance;
    mockDonationService = TestBed.inject(DonationService) as jasmine.SpyObj<DonationService>;
    mockRouter = TestBed.inject(Router) as jasmine.SpyObj<Router>;
    mockSeoService = TestBed.inject(SeoService) as jasmine.SpyObj<SeoService>;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.amount).toBe(10);
    expect(component.donorEmail).toBe('');
    expect(component.donorName).toBe('');
    expect(component.isProcessing).toBeFalse();
    expect(component.error).toBe('');
  });

  it('should select amount', () => {
    component.selectAmount(25);
    expect(component.amount).toBe(25);
  });

  it('should call seoService.updateSEO on init', () => {
    expect(mockSeoService.updateSEO).toHaveBeenCalled();
  });
});