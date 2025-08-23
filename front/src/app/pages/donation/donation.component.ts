import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { DonationService } from '../../services/donation.service';
import { catchError, finalize } from 'rxjs/operators';
import { of } from 'rxjs';
import { SeoService } from '../../services/seo.service';

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
    private router: Router,
    private readonly seoService: SeoService
  ) {}

  ngOnInit() {
    this.seoService.updateSEO({
      title: 'Blob - Faites un don pour soutenir notre plateforme',
      description: 'Soutenez Blob et contribuez au développement des quiz interactifs éducatifs. Chaque don nous aide à innover et offrir plus de contenus.',
      keywords: 'faire un don, contribution, financement, soutenir Blob, quiz éducatif, plateforme',
      ogTitle: 'Faites un don à Blob',
      ogDescription: 'Participez au développement de Blob et aidez-nous à offrir des quiz interactifs innovants pour l’apprentissage.',
      ogUrl: '/faire-un-don'
    });
  }
  selectAmount(amount: number) {
    this.amount = amount;
  }

  submitDonation() {
    if (this.isProcessing) return;

    this.isProcessing = true;
    this.error = '';

    this.donationService.createPaymentLink({
      amount: this.amount,
      donor_email: this.donorEmail || undefined,
      donor_name: this.donorName || undefined
    }).pipe(
      catchError((err: any) => {
        console.error('Erreur lors de la création du lien de paiement:', err);

        if (err.status === 500) {
          this.error = 'Le service de paiement est temporairement indisponible. Veuillez réessayer plus tard.';
        } else if (err.status === 400) {
          this.error = 'Veuillez vérifier les informations saisies.';
        } else if (err.status === 0 || err.status === 404) {
          this.error = 'Impossible de contacter le serveur. Vérifiez votre connexion.';
        } else {
          this.error = 'Une erreur inattendue s\'est produite. Veuillez réessayer.';
        }

        return of(null);
      }),
      finalize(() => {
        this.isProcessing = false;
      })
    ).subscribe(response => {
      if (response && response.payment_url) {
        window.location.href = response.payment_url;
      }
    });
  }

  goBack() {
    this.router.navigate(['/']);
  }
}
