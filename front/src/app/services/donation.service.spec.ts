import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { of, throwError } from 'rxjs';

import { DonationService } from './donation.service';
import { environment } from '../../environments/environment';

describe('DonationService', () => {
  let service: DonationService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [DonationService],
    });
    service = TestBed.inject(DonationService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should create payment link successfully', () => {
    const donationRequest = {
      amount: 1000,
      currency: 'eur',
      description: 'Donation for Blob',
      customerEmail: 'test@example.com',
    };

    const mockResponse = {
      paymentLinkId: 'plink_123',
      url: 'https://checkout.stripe.com/pay/plink_123',
      success: true,
    };

    service.createPaymentLink(donationRequest).subscribe(response => {
      expect(response).toEqual(jasmine.objectContaining(mockResponse));
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/donations/create-payment-link`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(donationRequest);
    req.flush(mockResponse);
  });

  it('should handle payment link creation error', () => {
    const donationRequest = {
      amount: 1000,
      currency: 'eur',
      description: 'Donation for Blob',
      customerEmail: 'test@example.com',
    };

    service.createPaymentLink(donationRequest).subscribe({
      next: () => fail('Should have failed'),
      error: error => {
        expect(error).toBeTruthy();
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/donations/create-payment-link`);
    req.flush('Error', { status: 400, statusText: 'Bad Request' });
  });

  it('should validate donation amount', () => {
    const validDonation = {
      amount: 500,
      currency: 'eur',
      description: 'Valid donation',
      customerEmail: 'test@example.com',
    };

    service.createPaymentLink(validDonation).subscribe(response => {
      expect(response).toBeTruthy();
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/donations/create-payment-link`);
    req.flush({ success: true });
  });

  it('should handle network error', () => {
    const donationRequest = {
      amount: 1000,
      currency: 'eur',
      description: 'Donation for Blob',
      customerEmail: 'test@example.com',
    };

    service.createPaymentLink(donationRequest).subscribe({
      next: () => fail('Should have failed'),
      error: error => {
        expect(error).toBeTruthy();
      },
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/donations/create-payment-link`);
    req.error(new ProgressEvent('Network error'));
  });

  it('should handle different currencies', () => {
    const donationRequest = {
      amount: 1000,
      currency: 'usd',
      description: 'USD donation',
      customerEmail: 'test@example.com',
    };

    service.createPaymentLink(donationRequest).subscribe(response => {
      expect(response).toBeTruthy();
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/donations/create-payment-link`);
    req.flush({ success: true });
  });

  it('should handle missing email', () => {
    const donationRequest = {
      amount: 1000,
      currency: 'eur',
      description: 'Donation without email',
    };

    service.createPaymentLink(donationRequest).subscribe(response => {
      expect(response).toBeTruthy();
    });

    const req = httpMock.expectOne(`${environment.apiBaseUrl}/donations/create-payment-link`);
    req.flush({ success: true });
  });
});
