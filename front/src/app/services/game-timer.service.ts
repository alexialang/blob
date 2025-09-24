import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, Subscription, interval } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class GameTimerService {
  private timeLeft$ = new BehaviorSubject<number>(0);
  private phase$ = new BehaviorSubject<string>('waiting');
  private isRunning$ = new BehaviorSubject<boolean>(false);

  private timerSubscription?: Subscription;
  private serverTimestamp = 0;
  private localStartTime = 0;
  private duration = 30;

  constructor() {}

  getTimeLeft(): Observable<number> {
    return this.timeLeft$.asObservable();
  }

  getPhase(): Observable<string> {
    return this.phase$.asObservable();
  }

  isRunning(): Observable<boolean> {
    return this.isRunning$.asObservable();
  }

  startSynchronizedTimer(serverTimestamp: number, duration: number, phase: string): void {
    this.stopTimer();

    this.duration = duration;
    this.serverTimestamp = serverTimestamp;
    this.localStartTime = Date.now();
    this.phase$.next(phase);
    this.isRunning$.next(true);

    const serverElapsed = Math.floor((Date.now() - serverTimestamp) / 1000);
    const initialTimeLeft = Math.max(0, duration - serverElapsed);

    this.timeLeft$.next(initialTimeLeft);

    this.timerSubscription = interval(1000).subscribe(() => {
      this.updateTimer();
    });
  }

  startLocalTimer(duration: number, phase: string): void {
    this.stopTimer();

    this.duration = duration;
    this.localStartTime = Date.now();
    this.phase$.next(phase);
    this.isRunning$.next(true);
    this.timeLeft$.next(duration);

    this.timerSubscription = interval(1000).subscribe(() => {
      this.updateLocalTimer();
    });
  }

  updateFromServer(serverTimeLeft: number, phase: string): void {
    this.timeLeft$.next(Math.max(0, serverTimeLeft));
    this.phase$.next(phase);

    if (serverTimeLeft <= 0) {
      this.stopTimer();
    }
  }

  stopTimer(): void {
    if (this.timerSubscription) {
      this.timerSubscription.unsubscribe();
      this.timerSubscription = undefined;
    }
    this.isRunning$.next(false);
    this.timeLeft$.next(0);
  }

  private updateTimer(): void {
    const elapsedSinceStart = Math.floor((Date.now() - this.localStartTime) / 1000);
    const serverElapsed = Math.floor((Date.now() - this.serverTimestamp) / 1000);
    const timeLeft = Math.max(0, this.duration - serverElapsed);

    this.timeLeft$.next(timeLeft);

    if (timeLeft <= 0) {
      this.stopTimer();
    }
  }

  private updateLocalTimer(): void {
    const elapsed = Math.floor((Date.now() - this.localStartTime) / 1000);
    const timeLeft = Math.max(0, this.duration - elapsed);

    this.timeLeft$.next(timeLeft);

    if (timeLeft <= 0) {
      this.stopTimer();
    }
  }

  getCurrentTime(): number {
    return this.timeLeft$.value;
  }

  getCurrentPhase(): string {
    return this.phase$.value;
  }

  isTimerRunning(): boolean {
    return this.isRunning$.value;
  }

  destroy(): void {
    this.stopTimer();
  }
}
