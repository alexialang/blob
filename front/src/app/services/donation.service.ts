import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface PaymentLinkResponse {
  payment_url: string;
  payment_link_id: string;
  donation_id: number;
}

export interface DonationRequest {
  amount: number;
  donor_email?: string;
  donor_name?: string;
}

@Injectable({
  providedIn: 'root'
})
export class DonationService {
  private apiUrl = `${environment.apiUrl}/api/donations`;

  constructor(private http: HttpClient) {}

  createPaymentLink(donation: DonationRequest): Observable<PaymentLinkResponse> {
    return this.http.post<PaymentLinkResponse>(`${this.apiUrl}/create-payment-link`, donation);
  }

}
