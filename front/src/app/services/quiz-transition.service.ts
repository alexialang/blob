import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { QuizCard } from '../models/quiz.model';

interface CardPosition {
  x: number;
  y: number;
  width: number;
  height: number;
}

@Injectable({
  providedIn: 'root'
})
export class QuizTransitionService {
  private _showTransition = new BehaviorSubject<boolean>(false);
  private _currentQuiz = new BehaviorSubject<QuizCard | null>(null);
  private _cardPosition = new BehaviorSubject<CardPosition | null>(null);
  private _isExiting = new BehaviorSubject<boolean>(false);

  showTransition$ = this._showTransition.asObservable();
  currentQuiz$ = this._currentQuiz.asObservable();
  cardPosition$ = this._cardPosition.asObservable();
  isExiting$ = this._isExiting.asObservable();

  startTransition(quiz: QuizCard, cardElement: HTMLElement): Promise<void> {
    return new Promise((resolve) => {
      const rect = cardElement.getBoundingClientRect();
      this._cardPosition.next({
        x: rect.left + rect.width / 2,
        y: rect.top + rect.height / 2,
        width: rect.width,
        height: rect.height
      });

      this._currentQuiz.next(quiz);
      this._showTransition.next(true);

      document.body.style.overflow = 'hidden';

      setTimeout(() => {
        this.endTransition();
        resolve();
      }, 8000);
    });
  }




  private endTransition(): void {
    this._showTransition.next(false);
    this._currentQuiz.next(null);
    this._cardPosition.next(null);
    this._isExiting.next(false);
    document.body.style.overflow = 'auto';
  }
}
