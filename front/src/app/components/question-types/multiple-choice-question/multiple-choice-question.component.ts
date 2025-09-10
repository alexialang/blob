import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Question } from '../../../models/quiz.model';

@Component({
  selector: 'app-multiple-choice-question',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './multiple-choice-question.component.html',
  styleUrls: ['./multiple-choice-question.component.scss'],
})
export class MultipleChoiceQuestionComponent {
  @Input() question!: Question;
  @Input() progress: { current: number; total: number; percentage: number } = {
    current: 0,
    total: 0,
    percentage: 0,
  };
  @Output() answerSelected = new EventEmitter<number[]>();
  @Output() answerValidated = new EventEmitter<void>();

  selectedAnswerIds: number[] = [];

  toggleAnswer(answerId: number): void {
    const index = this.selectedAnswerIds.indexOf(answerId);
    if (index > -1) {
      this.selectedAnswerIds.splice(index, 1);
    } else {
      this.selectedAnswerIds.push(answerId);
    }
    this.answerSelected.emit([...this.selectedAnswerIds]);
  }

  validateAnswer(): void {
    if (this.selectedAnswerIds.length > 0) {
      this.answerValidated.emit();
    }
  }

  isSelected(answerId: number): boolean {
    return this.selectedAnswerIds.includes(answerId);
  }
}
