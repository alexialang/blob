import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-payment-success',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './payment-success.component.html',
  styleUrl: './payment-success.component.scss'
})
export class PaymentSuccessComponent implements OnInit {
  sessionId: string | null = null;
  amount: number | null = null;
  donorName: string | null = null;
  isLoading = true;
  error = '';

  constructor(
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Récupérer les paramètres de l'URL
    this.sessionId = this.route.snapshot.queryParamMap.get('session_id');
    
    // Récupérer les données depuis les paramètres de l'URL ou le localStorage
    const urlParams = new URLSearchParams(window.location.search);
    const amount = urlParams.get('amount');
    const donorName = urlParams.get('donor_name');
    
    if (amount) {
      this.amount = parseFloat(amount);
    }
    
    if (donorName) {
      this.donorName = donorName;
    }

    // Simuler un délai de chargement pour l'effet visuel
    setTimeout(() => {
      this.isLoading = false;
    }, 1500);

    // Nettoyer les paramètres de l'URL après 5 secondes
    setTimeout(() => {
      this.router.navigate(['/'], { replaceUrl: true });
    }, 10000);
  }

  goToHome() {
    this.router.navigate(['/']);
  }

  goToDonation() {
    this.router.navigate(['/donation']);
  }
}
