import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { AnalyticsService } from './analytics.service';

export interface CookieConsent {
  necessary: boolean;
  analytics: boolean;
  marketing: boolean;
  functional: boolean;
}

export interface CookieConsentState {
  hasConsented: boolean;
  consent: CookieConsent;
  timestamp: number;
}

@Injectable({
  providedIn: 'root'
})
export class CookieConsentService {
  private readonly STORAGE_KEY = 'blob_cookie_consent';
  private readonly CONSENT_EXPIRY_DAYS = 365;

  private consentState$ = new BehaviorSubject<CookieConsentState>({
    hasConsented: false,
    consent: {
      necessary: true,
      analytics: false,
      marketing: false,
      functional: false
    },
    timestamp: 0
  });

  public readonly consentState = this.consentState$.asObservable();

  constructor(private analytics: AnalyticsService) {
    this.loadSavedConsent();
    this.setupDefaultConsent();
  }

  private setupDefaultConsent(): void {
    // Config par défaut pour Google Analytics
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('consent', 'default', {
        analytics_storage: 'denied',
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        wait_for_update: 500
      });
    }
  }

  private loadSavedConsent(): void {
    if (typeof window === 'undefined') return;

    try {
      const saved = localStorage.getItem(this.STORAGE_KEY);
      if (saved) {
        const savedState: CookieConsentState = JSON.parse(saved);

        // Vérifier si le consentement n'a pas expiré
        const expiryDate = new Date(savedState.timestamp + (this.CONSENT_EXPIRY_DAYS * 24 * 60 * 60 * 1000));

        if (new Date() < expiryDate) {
          this.consentState$.next(savedState);
          this.applyConsent(savedState.consent);
        } else {
          // Consentement expiré, le supprimer
          this.clearConsent();
        }
      }
    } catch (error) {
      console.error('Erreur lors du chargement du consentement:', error);
    }
  }

  private saveConsent(consent: CookieConsent): void {
    if (typeof window === 'undefined') return;

    const state: CookieConsentState = {
      hasConsented: true,
      consent,
      timestamp: Date.now()
    };

    try {
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(state));
      this.consentState$.next(state);
    } catch (error) {
      console.error('Erreur lors de la sauvegarde du consentement:', error);
    }
  }

  private applyConsent(consent: CookieConsent): void {
    // Appliquer le consentement
    this.analytics.updateConsentMode(consent.analytics, consent.marketing);

    // Si l'analytics est refusé, désactiver le tracking
    if (!consent.analytics) {
      this.analytics.disableTracking();
    }

    console.log('🍪 Consentement appliqué:', consent);
  }

  acceptAll(): void {
    const consent: CookieConsent = {
      necessary: true,
      analytics: true,
      marketing: true,
      functional: true
    };

    this.saveConsent(consent);
    this.applyConsent(consent);

    this.analytics.trackEvent({
      action: 'cookie_consent_accept_all',
      category: 'privacy'
    });
  }

  rejectAll(): void {
    const consent: CookieConsent = {
      necessary: true,
      analytics: false,
      marketing: false,
      functional: false
    };

    this.saveConsent(consent);
    this.applyConsent(consent);

    this.analytics.trackEvent({
      action: 'cookie_consent_reject_all',
      category: 'privacy'
    });
  }

  /**
   * Consentement personnalisé
   */
  setCustomConsent(consent: Partial<CookieConsent>): void {
    const fullConsent: CookieConsent = {
      necessary: true,
      analytics: consent.analytics ?? false,
      marketing: consent.marketing ?? false,
      functional: consent.functional ?? false
    };

    this.saveConsent(fullConsent);
    this.applyConsent(fullConsent);

    this.analytics.trackEvent({
      action: 'cookie_consent_custom',
      category: 'privacy',
      custom_parameters: fullConsent
    });
  }

  clearConsent(): void {
    if (typeof window === 'undefined') return;

    localStorage.removeItem(this.STORAGE_KEY);

    const defaultState: CookieConsentState = {
      hasConsented: false,
      consent: {
        necessary: true,
        analytics: false,
        marketing: false,
        functional: false
      },
      timestamp: 0
    };

    this.consentState$.next(defaultState);
    this.setupDefaultConsent();
  }

  isConsentGiven(type: keyof CookieConsent): boolean {
    const currentState = this.consentState$.value;
    return currentState.hasConsented && currentState.consent[type];
  }

  hasUserConsented(): boolean {
    return this.consentState$.value.hasConsented;
  }

  getCurrentConsent(): CookieConsent {
    return { ...this.consentState$.value.consent };
  }

  reopenConsentBanner(): void {
    this.clearConsent();
  }

  getCookieInfo() {
    return {
      necessary: {
        name: 'Cookies nécessaires',
        description: 'Ces cookies sont essentiels au fonctionnement du site et ne peuvent pas être désactivés.',
        cookies: [
          'blob_cookie_consent - Stocke vos préférences de cookies',
          'session - Maintient votre session de connexion',
          'csrf_token - Protection contre les attaques CSRF'
        ]
      },
      analytics: {
        name: 'Cookies d\'analyse',
        description: 'Ces cookies nous aident à comprendre comment vous utilisez notre site pour l\'améliorer.',
        cookies: [
          '_ga - Google Analytics (2 ans)',
          '_gid - Google Analytics (24 heures)',
          '_gat - Google Analytics (1 minute)'
        ]
      },
      marketing: {
        name: 'Cookies marketing',
        description: 'Ces cookies sont utilisés pour vous proposer des publicités pertinentes.',
        cookies: [
          '_gcl_au - Google AdSense (90 jours)',
          'fr - Facebook Pixel (90 jours)'
        ]
      },
      functional: {
        name: 'Cookies fonctionnels',
        description: 'Ces cookies améliorent votre expérience en mémorisant vos préférences.',
        cookies: [
          'theme_preference - Votre thème préféré',
          'language_preference - Votre langue préférée'
        ]
      }
    };
  }
}

