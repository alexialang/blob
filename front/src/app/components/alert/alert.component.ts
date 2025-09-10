import { Component, OnInit, OnDestroy, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AlertService, AlertMessage } from '../../services/alert.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-alert',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="alert-container" *ngIf="currentAlert">
      <div class="alert" [ngClass]="'alert-' + currentAlert.type">
        <span class="alert-message">{{ currentAlert.message }}</span>
        <button class="alert-close" (click)="closeAlert()">&times;</button>
      </div>
    </div>
  `,
  styles: [
    `
      .alert-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
      }

      .alert {
        padding: 12px 20px;
        border-radius: 4px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-width: 300px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      }

      .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
      }

      .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
      }

      .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
      }

      .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
      }

      .alert-close {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        margin-left: 10px;
        opacity: 0.7;
      }

      .alert-close:hover {
        opacity: 1;
      }
    `,
  ],
})
export class AlertComponent implements OnInit, OnDestroy {
  private alertService = inject(AlertService);
  private subscription: Subscription | null = null;

  currentAlert: AlertMessage | null = null;
  private timeoutId: any;

  ngOnInit(): void {
    this.subscription = this.alertService.alerts$.subscribe(alert => {
      this.showAlert(alert);
    });
  }

  ngOnDestroy(): void {
    if (this.subscription) {
      this.subscription.unsubscribe();
    }
    if (this.timeoutId) {
      clearTimeout(this.timeoutId);
    }
  }

  private showAlert(alert: AlertMessage): void {
    this.currentAlert = alert;

    if (this.timeoutId) {
      clearTimeout(this.timeoutId);
    }

    if (alert.duration && alert.duration > 0) {
      this.timeoutId = setTimeout(() => {
        this.closeAlert();
      }, alert.duration);
    }
  }

  closeAlert(): void {
    this.currentAlert = null;
    if (this.timeoutId) {
      clearTimeout(this.timeoutId);
      this.timeoutId = null;
    }
  }
}
