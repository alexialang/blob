import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { DonationService } from '../../services/donation.service';

@Component({
  selector: 'app-donation',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './donation.component.html',
  styleUrl: './donation.component.scss'
})
export class DonationComponent implements OnInit {
  amount: number = 10;
  donorEmail: string = '';
  donorName: string = '';
  isProcessing: boolean = false;
  error: string = '';

  predefinedAmounts = [5, 10, 25, 50, 100];

  constructor(
    private donationService: DonationService,
    private router: Router
  ) {}

  ngOnInit() {
  }

  selectAmount(amount: number) {
    this.amount = amount;
  }

  async submitDonation() {
    if (this.isProcessing) return;

    this.isProcessing = true;
    this.error = '';

    try {
      const response = await this.donationService.createPaymentLink({
        amount: this.amount,
        donor_email: this.donorEmail || undefined,
        donor_name: this.donorName || undefined
      }).toPromise();

      if (!response) {
        throw new Error('Erreur lors de la création du paiement');
      }

      window.location.href = response.payment_url;

    } catch (err: any) {
      this.error = err.message || 'Une erreur est survenue';
      this.isProcessing = false;
    }
  }

  goBack() {
    this.router.navigate(['/']);
  }
}
