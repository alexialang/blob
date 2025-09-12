import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { DonationService, DonationRequest, PaymentLinkResponse } from './donation.service';
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

  it('should create payment link', () => {
    const mockDonation: DonationRequest = {
      amount: 25,
      donor_email: 'test@example.com',
      donor_name: 'Test User',
    };

    const mockResponse: PaymentLinkResponse = {
      payment_url: 'https://checkout.stripe.com/test',
      payment_link_id: 'test-link-id',
      donation_id: 123,
    };

    service.createPaymentLink(mockDonation).subscribe(response => {
      expect(response).toEqual(mockResponse);
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/api/donations/create-payment-link`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(mockDonation);
    req.flush(mockResponse);
  });

  it('should handle error when creating payment link', () => {
    const mockDonation: DonationRequest = {
      amount: 25,
    };

    service.createPaymentLink(mockDonation).subscribe({
      next: () => fail('should have failed'),
      error: error => {
        expect(error.status).toBe(500);
      },
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/api/donations/create-payment-link`);
    req.flush('Server error', { status: 500, statusText: 'Internal Server Error' });
  });
});
