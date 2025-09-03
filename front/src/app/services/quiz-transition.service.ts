import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { QuizCard } from '../models/quiz.model';

interface CardPosition {
  x: number;
  y: number;
  width: number;
  height: number;
}

interface TransitionData {
  quiz: QuizCard;
  cardColor: string;
}

@Injectable({
  providedIn: 'root'
})
export class QuizTransitionService {
  private _showTransition = new BehaviorSubject<boolean>(false);
  private _currentQuiz = new BehaviorSubject<QuizCard | null>(null);
  private _cardPosition = new BehaviorSubject<CardPosition | null>(null);
  private _isExiting = new BehaviorSubject<boolean>(false);
  private _cardColor = new BehaviorSubject<string>('var(--color-primary)');

  showTransition$ = this._showTransition.asObservable();
  currentQuiz$ = this._currentQuiz.asObservable();
  cardPosition$ = this._cardPosition.asObservable();
  isExiting$ = this._isExiting.asObservable();
  cardColor$ = this._cardColor.asObservable();

  startTransition(quiz: QuizCard, cardElement: HTMLElement, cardColor: string): Promise<void> {
    return new Promise((resolve) => {
      const rect = cardElement.getBoundingClientRect();
      this._cardPosition.next({
        x: rect.left + rect.width / 2,
        y: rect.top + rect.height / 2,
        width: rect.width,
        height: rect.height
      });

      this._currentQuiz.next(quiz);
      this._cardColor.next(cardColor);
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
    this._cardColor.next('var(--color-primary)');
    document.body.style.overflow = 'auto';
  }
}
