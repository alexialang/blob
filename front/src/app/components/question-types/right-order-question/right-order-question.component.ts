import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CdkDragDrop, moveItemInArray, DragDropModule } from '@angular/cdk/drag-drop';
import { Question, Answer } from '../../../models/quiz.model';

@Component({
  selector: 'app-right-order-question',
  standalone: true,
  imports: [CommonModule, DragDropModule],
  templateUrl: './right-order-question.component.html',
  styleUrls: ['./right-order-question.component.scss']
})
export class RightOrderQuestionComponent {
  @Input() question!: Question;
  @Input() progress: { current: number; total: number; percentage: number } = { current: 0, total: 0, percentage: 0 };
  @Output() answerSelected = new EventEmitter<number[]>();
  @Output() answerValidated = new EventEmitter<void>();

  orderedAnswers: Answer[] = [];

  ngOnInit(): void {
    this.orderedAnswers = [...this.question.answers].sort(() => Math.random() - 0.5);
  }

  drop(event: CdkDragDrop<Answer[]>): void {
    moveItemInArray(this.orderedAnswers, event.previousIndex, event.currentIndex);

    const orderIds = this.orderedAnswers.map(answer => answer.id);
    this.answerSelected.emit(orderIds);
  }

  validateAnswer(): void {
    this.answerValidated.emit();
  }

  resetOrder(): void {
    this.orderedAnswers = [...this.question.answers].sort(() => Math.random() - 0.5);
    this.answerSelected.emit([]);
  }
}
