import { Injectable } from '@angular/core';
import { environment } from '../../environments/environment';

export interface AnalyticsEvent {
  name: string;
  properties?: { [key: string]: any };
}

@Injectable({
  providedIn: 'root',
})
export class PrivacyAnalyticsService {
  private isEnabled = environment.analytics.enabled;
  private respectPrivacy = environment.analytics.respectPrivacy;

  constructor() {
    // On initialise seulement si le consentement a été donné
    this.checkConsentAndInitialize();
  }

  /**
   * Vérifie le consentement et initialise si nécessaire
   */
  private checkConsentAndInitialize(): void {
    if (typeof window === 'undefined') return;

    const consent = this.getStoredConsent();
    if (consent?.analytics && this.isEnabled && this.respectPrivacy) {
      this.initializeUmamiAnalytics();
    }
  }

  /**
   * Récupère le consentement stocké
   */
  private getStoredConsent(): any {
    try {
      const consent = localStorage.getItem('blob_privacy_consent');
      return consent ? JSON.parse(consent) : null;
    } catch {
      return null;
    }
  }

  /**
   * Active les analytics après consentement
   */
  enableAnalytics(): void {
    if (this.isEnabled && this.respectPrivacy) {
      this.initializeUmamiAnalytics();
    }
  }

  /**
   * Initialise Umami Analytics (respectueux de la vie privée)
   * Alternative gratuite et RGPD-friendly à Google Analytics
   */
  private initializeUmamiAnalytics(): void {
    if (typeof window === 'undefined') return;

    // Umami Analytics - Gratuit, open source, respecte la vie privée
    const script = document.createElement('script');
    script.async = true;
    script.defer = true;
    script.src = `${environment.analytics.umamiUrl}/script.js`;
    script.setAttribute('data-website-id', environment.analytics.umamiWebsiteId);
    script.setAttribute('data-domains', window.location.hostname);
    script.setAttribute('data-do-not-track', 'true'); // Respecte Do Not Track
    script.setAttribute('data-cache', 'true'); // Cache pour performance
    script.setAttribute('data-auto-track', 'false'); // Désactive le tracking automatique
    script.setAttribute('data-exclude-search', 'true'); // Exclut les paramètres de recherche

    document.head.appendChild(script);
  }

  /**
   * Envoie un événement d'analytics (respectueux de la vie privée)
   */
  trackEvent(event: AnalyticsEvent): void {
    if (!this.isEnabled || !this.respectPrivacy) return;

    // Vérifier le consentement avant de tracker
    const consent = this.getStoredConsent();
    if (!consent?.analytics) return;

    // Umami Analytics events
    if (typeof window !== 'undefined' && (window as any).umami) {
      (window as any).umami.track(event.name, event.properties);
    }
  }

  /**
   * Suivi de page (respectueux de la vie privée)
   */
  trackPageView(url: string, title?: string): void {
    if (!this.isEnabled || !this.respectPrivacy) return;

    // Vérifier le consentement
    const consent = this.getStoredConsent();
    if (!consent?.analytics) return;

    // Umami gère automatiquement les vues de page
    // Optionnellement, on peut forcer une vue de page
    if (typeof window !== 'undefined' && (window as any).umami) {
      (window as any).umami.track('pageview', { url, title });
    }
  }



  /**
   * Méthodes de tracking spécifiques pour l'application
   */
  trackQuizStarted(quizId: number): void {
    this.trackEvent({
      name: 'quiz_started',
      properties: { quiz_id: quizId },
    });
  }

  trackQuizCompleted(quizId: number, score: number): void {
    this.trackEvent({
      name: 'quiz_completed',
      properties: { quiz_id: quizId, score },
    });
  }

  trackUserRegistration(): void {
    this.trackEvent({
      name: 'user_registration',
    });
  }

  trackGameCreated(gameType: string): void {
    this.trackEvent({
      name: 'game_created',
      properties: { game_type: gameType },
    });
  }

  trackFeatureUsed(feature: string): void {
    this.trackEvent({
      name: 'feature_used',
      properties: { feature },
    });
  }

  /**
   * Nettoie les données de consentement
   */
  clearConsentData(): void {
    try {
      localStorage.removeItem('blob_privacy_consent');
      console.log('✅ Données de consentement supprimées');
    } catch (error) {
      console.warn('Erreur lors du nettoyage du consentement:', error);
    }
  }
}
