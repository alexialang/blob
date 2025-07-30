import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { QuizCard } from '../../models/quiz.model';

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
  @Output() quizLike = new EventEmitter<QuizCard>();
  @Output() quizFlip = new EventEmitter<QuizCard>();
  @Output() playModeChange = new EventEmitter<QuizCard>();

  startQuiz() {
    this.quizStart.emit(this.quiz);
  }

  flipCard() {
    this.quizFlip.emit(this.quiz);
  }

  togglePlayMode() {
    this.playModeChange.emit(this.quiz);
  }

  getCompanyBadgeText(): string {
    if (!this.quiz.is_public && this.quiz.groupName) {
      return this.quiz.groupName;
    }
    return '';
  }
}
