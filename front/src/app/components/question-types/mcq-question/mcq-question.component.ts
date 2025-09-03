import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Question } from '../../../models/quiz.model';

@Component({
  selector: 'app-mcq-question',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './mcq-question.component.html',
  styleUrls: ['./mcq-question.component.scss']
})
export class McqQuestionComponent {
  @Input() question!: Question;
  @Input() progress: { current: number; total: number; percentage: number } = { current: 0, total: 0, percentage: 0 };
  @Output() answerSelected = new EventEmitter<number>();
  @Output() answerValidated = new EventEmitter<void>();

  selectedAnswerId: number | null = null;

  selectAnswer(answerId: number): void {
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

  getFlowerShape(index: number): string {
    const shapes = [
      '/assets/svg/blob_flower_color.png',
      '/assets/svg/blob_flower color2.png',
      '/assets/svg/blob_flower_color3.png',
      '/assets/svg/blob_flower_color4.png'
    ];
    return shapes[index % shapes.length];
  }
}
