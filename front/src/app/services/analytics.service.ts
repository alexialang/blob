import { Injectable, Inject } from '@angular/core';
import { DOCUMENT } from '@angular/common';
import { Router, NavigationEnd } from '@angular/router';
import { filter } from 'rxjs/operators';
import { environment } from '../../environments/environment';

declare global {
  interface Window {
    gtag: (...args: any[]) => void;
    dataLayer: any[];
  }
}



export interface AnalyticsEvent {
  action: string;
  category: string;
  label?: string;
  value?: number;
  custom_parameters?: { [key: string]: any };
}

@Injectable({
  providedIn: 'root'
})
export class AnalyticsService {
  private isAnalyticsLoaded = false;
  private gaId = environment.analytics.googleAnalyticsId;
  private gtmId = environment.analytics.googleTagManagerId;
  private analyticsEnabled = environment.analytics.enabled;

  constructor(
    @Inject(DOCUMENT) private document: Document,
    private router: Router
  ) {
    if (this.analyticsEnabled) {
      this.initializeAnalytics();
      this.trackPageViews();
    }
  }

  private initializeAnalytics(): void {
    if (typeof window !== 'undefined' && typeof window.gtag === 'function') {
      this.isAnalyticsLoaded = true;
    } else {
      console.warn('Google Analytics not loaded');
    }
  }

  private trackPageViews(): void {
    this.router.events
      .pipe(filter(event => event instanceof NavigationEnd))
      .subscribe((event: NavigationEnd) => {
        this.trackPageView(event.urlAfterRedirects);
      });
  }

  /**
   * Suivi des pages vues
   */
    trackPageView(url: string): void {
    if (!this.analyticsEnabled || !this.isAnalyticsLoaded) return;

    window.gtag('config', this.gaId, {
      page_path: url,
      anonymize_ip: true
    });

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'page_view',
      page_location: window.location.href,
      page_path: url,
      page_title: this.document.title
    });

    console.log('📊 Page view tracked:', url);
  }

  /**
   * Suivi des événements personnalisés
   */
    trackEvent(event: AnalyticsEvent): void {
    if (!this.analyticsEnabled || !this.isAnalyticsLoaded) return;

    window.gtag('event', event.action, {
      event_category: event.category,
      event_label: event.label,
      value: event.value,
      ...event.custom_parameters
    });

    window.dataLayer.push({
      event: 'custom_event',
      event_action: event.action,
      event_category: event.category,
      event_label: event.label,
      event_value: event.value,
      ...event.custom_parameters
    });
  }

    /**
   * Événements correspondant aux vraies fonctionnalités de l'app
   */

  trackLogin(): void {
    this.trackEvent({
      action: 'login',
      category: 'authentication'
    });
  }

  trackRegistration(): void {
    this.trackEvent({
      action: 'registration',
      category: 'authentication'
    });
  }

  trackQuizView(): void {
    this.trackEvent({
      action: 'quiz_view',
      category: 'quiz'
    });
  }

  trackQuizStart(quizId: string): void {
    this.trackEvent({
      action: 'quiz_start',
      category: 'quiz',
      custom_parameters: { quiz_id: quizId }
    });
  }

  trackQuizComplete(quizId: string, score: number): void {
    this.trackEvent({
      action: 'quiz_complete',
      category: 'quiz',
      value: score,
      custom_parameters: { quiz_id: quizId, score: score }
    });
  }

  trackQuizCreation(): void {
    this.trackEvent({
      action: 'quiz_create',
      category: 'content_creation'
    });
  }

  trackMultiplayerRoomCreate(): void {
    this.trackEvent({
      action: 'multiplayer_room_create',
      category: 'multiplayer'
    });
  }

  trackMultiplayerRoomJoin(): void {
    this.trackEvent({
      action: 'multiplayer_room_join',
      category: 'multiplayer'
    });
  }

  trackMultiplayerGameComplete(score: number): void {
    this.trackEvent({
      action: 'multiplayer_game_complete',
      category: 'multiplayer',
      value: score
    });
  }

  trackProfileView(): void {
    this.trackEvent({
      action: 'profile_view',
      category: 'user_engagement'
    });
  }

  trackAvatarSelection(): void {
    this.trackEvent({
      action: 'avatar_selection',
      category: 'user_engagement'
    });
  }

  trackLeaderboardView(): void {
    this.trackEvent({
      action: 'leaderboard_view',
      category: 'user_engagement'
    });
  }

  trackDonationPageView(): void {
    this.trackEvent({
      action: 'donation_page_view',
      category: 'conversion'
    });
  }

  trackError(error: string): void {
    this.trackEvent({
      action: 'exception',
      category: 'error',
      label: error
    });
  }

  /**
   * Consentement cookies (utilisé par cookie-consent.service)
   */
  updateConsentMode(analytics: boolean, marketing: boolean): void {
    if (!this.analyticsEnabled || !this.isAnalyticsLoaded) return;

    window.gtag('consent', 'update', {
      analytics_storage: analytics ? 'granted' : 'denied',
      ad_storage: marketing ? 'granted' : 'denied',
      ad_user_data: marketing ? 'granted' : 'denied',
      ad_personalization: marketing ? 'granted' : 'denied'
    });
  }

  /**
   * Désactiver le tracking (utilisé par cookie-consent.service)
   */
  disableTracking(): void {
    if (typeof window !== 'undefined') {
      (window as any)[`ga-disable-${this.gaId}`] = true;
    }
  }
}
