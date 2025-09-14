import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PrivacyAnalyticsService } from '../../services/privacy-analytics.service';

@Component({
  selector: 'app-privacy-consent',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './privacy-consent.component.html',
  styleUrls: ['./privacy-consent.component.scss'],
})
export class PrivacyConsentComponent implements OnInit {
  showConsentBanner = false;

  constructor(private analyticsService: PrivacyAnalyticsService) {}

  ngOnInit(): void {
    this.checkConsentStatus();
  }

  /**
   * Vérifie si l'utilisateur a déjà donné son consentement
   */
  private checkConsentStatus(): void {
    const consent = localStorage.getItem('blob_privacy_consent');
    if (!consent) {
      this.showConsentBanner = true;
    }
  }

  /**
   * Accepte les analytics respectueux de la vie privée
   */
  acceptAnalytics(): void {
    localStorage.setItem(
      'blob_privacy_consent',
      JSON.stringify({
        analytics: true,
        timestamp: Date.now(),
      })
    );
    this.showConsentBanner = false;

    // Tracker le consentement
    this.analyticsService.trackEvent({
      name: 'privacy_consent_accepted',
    });
  }

  /**
   * Refuse les analytics
   */
  declineAnalytics(): void {
    localStorage.setItem(
      'blob_privacy_consent',
      JSON.stringify({
        analytics: false,
        timestamp: Date.now(),
      })
    );
    this.showConsentBanner = false;

    // Nettoyer toutes les données existantes
    this.analyticsService.clearLocalData();
  }

  /**
   * Ouvre les paramètres de confidentialité détaillés
   */
  openPrivacySettings(): void {
    // Cette méthode peut ouvrir une modal détaillée
  }
}
