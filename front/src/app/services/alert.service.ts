import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

export interface AlertMessage {
  message: string;
  type: 'success' | 'error' | 'warning' | 'info';
  duration?: number;
}

@Injectable({
  providedIn: 'root'
})
export class AlertService {
  private alertSubject = new Subject<AlertMessage>();
  public alerts$ = this.alertSubject.asObservable();

  success(message: string, duration: number = 3000): void {
    this.alertSubject.next({ message, type: 'success', duration });
  }

  error(message: string, duration: number = 5000): void {
    this.alertSubject.next({ message, type: 'error', duration });
  }

  warning(message: string, duration: number = 4000): void {
    this.alertSubject.next({ message, type: 'warning', duration });
  }

  info(message: string, duration: number = 3000): void {
    this.alertSubject.next({ message, type: 'info', duration });
  }
}

