import { Component, Input, Output, EventEmitter, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { QuizCard } from '../../models/quiz.model';
import { QuizTransitionService } from '../../services/quiz-transition.service';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-quiz-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './quiz-card.component.html',
  styleUrls: ['./quiz-card.component.scss']
})
export class QuizCardComponent {
  @Input() quiz!: QuizCard;
  @Output() quizStart = new EventEmitter<QuizCard>();
  @Output() quizFlip = new EventEmitter<QuizCard>();
  @Output() playModeChange = new EventEmitter<QuizCard>();

  constructor(
    private quizTransitionService: QuizTransitionService,
    private elementRef: ElementRef,
    private authService: AuthService
  ) {}

  get isGuest(): boolean {
    return this.authService.isGuest();
  }

  async startQuiz() {
    if (this.quiz.playMode === 'team') {
      this.quizStart.emit(this.quiz);
      return;
    }

    const cardElement = this.elementRef.nativeElement.querySelector('.quiz-card');
    await this.quizTransitionService.startTransition(this.quiz, cardElement);
    this.quizStart.emit(this.quiz);
  }

  flipCard() {
    this.quizFlip.emit(this.quiz);
  }

  togglePlayMode() {
    if (this.isGuest) {
      return;
    }
    this.playModeChange.emit(this.quiz);
  }

  getCompanyBadgeText(): string {
    if (!this.quiz.is_public && this.quiz.groupName) {
      return this.quiz.groupName;
    }
    return '';
  }
}
