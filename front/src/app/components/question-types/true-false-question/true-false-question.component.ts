import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Question } from '../../../models/quiz.model';

@Component({
  selector: 'app-true-false-question',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './true-false-question.component.html',
  styleUrls: ['./true-false-question.component.scss']
})
export class TrueFalseQuestionComponent {
  @Input() question!: Question;
  @Input() progress: { current: number; total: number; percentage: number } = { current: 0, total: 0, percentage: 0 };
  @Output() answerSelected = new EventEmitter<number>();
  @Output() answerValidated = new EventEmitter<void>();

  selectedAnswerId: number | null = null;

  selectAnswer(answerId: number, isTrue: boolean): void {
    this.selectedAnswerId = answerId;
    this.answerSelected.emit(answerId);
  }

  validateAnswer(): void {
    if (this.selectedAnswerId) {
      this.answerValidated.emit();
    }
  }

  isSelected(answerId: number): boolean {
    return this.selectedAnswerId === answerId;
  }

  getTrueAnswer() {
    return this.question.answers.find(a => a.answer.toLowerCase().includes('vrai') || a.answer.toLowerCase().includes('true'));
  }

  getFalseAnswer() {
    return this.question.answers.find(a => a.answer.toLowerCase().includes('faux') || a.answer.toLowerCase().includes('false'));
  }
}
