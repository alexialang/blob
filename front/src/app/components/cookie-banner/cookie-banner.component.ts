import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Subscription } from 'rxjs';
import { CookieConsentService, CookieConsent } from '../../services/cookie-consent.service';

@Component({
  selector: 'app-cookie-banner',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div
      class="cookie-banner"
      [class.show]="showBanner"
      [class.show-details]="showDetails"
    >
      <div class="cookie-banner-content">

        <div class="simple-banner" *ngIf="!showDetails">
          <div class="banner-text">
            <h3>🍪 Nous utilisons des cookies</h3>
            <p>
              Nous utilisons des cookies pour améliorer votre expérience sur notre site.
              Certains sont nécessaires au fonctionnement, d'autres nous aident à vous proposer
              du contenu personnalisé et à analyser notre trafic.
            </p>
          </div>

          <div class="banner-actions">
            <button
              class="btn btn-outline"
              (click)="showCookieDetails()"
            >
              Personnaliser
            </button>
            <button
              class="btn btn-secondary"
              (click)="rejectAll()"
            >
              Refuser tout
            </button>
            <button
              class="btn btn-primary"
              (click)="acceptAll()"
            >
              Tout accepter
            </button>
          </div>
        </div>

        <div class="detailed-settings" *ngIf="showDetails">
          <div class="settings-header">
            <h3>Paramètres des cookies</h3>
            <button class="close-btn" (click)="closeDetails()" aria-label="Fermer">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="cookie-categories">

            <div class="cookie-category">
              <div class="category-header">
                <label class="cookie-switch">
                  <input
                    type="checkbox"
                    [checked]="true"
                    disabled
                  >
                  <span class="slider disabled"></span>
                </label>
                <div class="category-info">
                  <h4>Cookies nécessaires</h4>
                  <p>{{ cookieInfo.necessary.description }}</p>
                </div>
              </div>
              <div class="category-details" *ngIf="expandedCategory === 'necessary'">
                <ul>
                  <li *ngFor="let cookie of cookieInfo.necessary.cookies">{{ cookie }}</li>
                </ul>
              </div>
              <button
                class="expand-btn"
                (click)="toggleCategory('necessary')"
              >
                {{ expandedCategory === 'necessary' ? 'Masquer' : 'Voir les détails' }}
              </button>
            </div>

            <div class="cookie-category">
              <div class="category-header">
                <label class="cookie-switch">
                  <input
                    type="checkbox"
                    [(ngModel)]="tempConsent.analytics"
                  >
                  <span class="slider"></span>
                </label>
                <div class="category-info">
                  <h4>Cookies d'analyse</h4>
                  <p>{{ cookieInfo.analytics.description }}</p>
                </div>
              </div>
              <div class="category-details" *ngIf="expandedCategory === 'analytics'">
                <ul>
                  <li *ngFor="let cookie of cookieInfo.analytics.cookies">{{ cookie }}</li>
                </ul>
              </div>
              <button
                class="expand-btn"
                (click)="toggleCategory('analytics')"
              >
                {{ expandedCategory === 'analytics' ? 'Masquer' : 'Voir les détails' }}
              </button>
            </div>

            <div class="cookie-category">
              <div class="category-header">
                <label class="cookie-switch">
                  <input
                    type="checkbox"
                    [(ngModel)]="tempConsent.marketing"
                  >
                  <span class="slider"></span>
                </label>
                <div class="category-info">
                  <h4>Cookies marketing</h4>
                  <p>{{ cookieInfo.marketing.description }}</p>
                </div>
              </div>
              <div class="category-details" *ngIf="expandedCategory === 'marketing'">
                <ul>
                  <li *ngFor="let cookie of cookieInfo.marketing.cookies">{{ cookie }}</li>
                </ul>
              </div>
              <button
                class="expand-btn"
                (click)="toggleCategory('marketing')"
              >
                {{ expandedCategory === 'marketing' ? 'Masquer' : 'Voir les détails' }}
              </button>
            </div>

            <div class="cookie-category">
              <div class="category-header">
                <label class="cookie-switch">
                  <input
                    type="checkbox"
                    [(ngModel)]="tempConsent.functional"
                  >
                  <span class="slider"></span>
                </label>
                <div class="category-info">
                  <h4>Cookies fonctionnels</h4>
                  <p>{{ cookieInfo.functional.description }}</p>
                </div>
              </div>
              <div class="category-details" *ngIf="expandedCategory === 'functional'">
                <ul>
                  <li *ngFor="let cookie of cookieInfo.functional.cookies">{{ cookie }}</li>
                </ul>
              </div>
              <button
                class="expand-btn"
                (click)="toggleCategory('functional')"
              >
                {{ expandedCategory === 'functional' ? 'Masquer' : 'Voir les détails' }}
              </button>
            </div>

          </div>

          <div class="settings-actions">
            <button
              class="btn btn-outline"
              (click)="closeDetails()"
            >
              Annuler
            </button>
            <button
              class="btn btn-primary"
              (click)="saveCustomConsent()"
            >
              Enregistrer mes préférences
            </button>
          </div>

          <div class="privacy-links">
            <a href="/mentions-legales" target="_blank">Mentions légales</a>
            <a href="/politique-confidentialite" target="_blank">Politique de confidentialité</a>
          </div>
        </div>

      </div>

      <div class="cookie-overlay" *ngIf="showBanner" (click)="closeDetails()"></div>
    </div>
  `,
  styleUrls: ['./cookie-banner.component.scss']
})
export class CookieBannerComponent implements OnInit, OnDestroy {
  showBanner = false;
  showDetails = false;
  expandedCategory: string | null = null;

  tempConsent: CookieConsent = {
    necessary: true,
    analytics: false,
    marketing: false,
    functional: false
  };

  cookieInfo: any;
  private subscription = new Subscription();

  constructor(private cookieService: CookieConsentService) {
    this.cookieInfo = this.cookieService.getCookieInfo();
  }

  ngOnInit(): void {
    this.subscription.add(
      this.cookieService.consentState.subscribe(state => {
        this.showBanner = !state.hasConsented;
        this.tempConsent = { ...state.consent };
      })
    );
  }

  ngOnDestroy(): void {
    this.subscription.unsubscribe();
  }

  acceptAll(): void {
    this.cookieService.acceptAll();
    this.hideBanner();
  }

  rejectAll(): void {
    this.cookieService.rejectAll();
    this.hideBanner();
  }

  showCookieDetails(): void {
    this.showDetails = true;
    this.tempConsent = { ...this.cookieService.getCurrentConsent() };
  }

  closeDetails(): void {
    this.showDetails = false;
  }

  saveCustomConsent(): void {
    this.cookieService.setCustomConsent(this.tempConsent);
    this.hideBanner();
  }

  toggleCategory(category: string): void {
    this.expandedCategory = this.expandedCategory === category ? null : category;
  }

  private hideBanner(): void {
    this.showBanner = false;
    this.showDetails = false;
  }
}

