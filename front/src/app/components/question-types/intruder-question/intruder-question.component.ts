import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Question, Answer } from '../../../models/quiz.model';

@Component({
  selector: 'app-intruder-question',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './intruder-question.component.html',
  styleUrls: ['./intruder-question.component.scss'],
})
export class IntruderQuestionComponent implements OnInit {
  @Input() question!: Question;
  @Input() progress: { current: number; total: number; percentage: number } = {
    current: 0,
    total: 0,
    percentage: 0,
  };
  @Output() answerSelected = new EventEmitter<number>();
  @Output() answerValidated = new EventEmitter<void>();

  selectedAnswerId: number | null = null;
  shuffledAnswers: Answer[] = [];

  hoveredCard: number | null = null;

  isHoveringCard = false;
  cursorPosition = { x: 0, y: 0 };

  isAccused = false;

  ngOnInit(): void {
    const limitedAnswers = this.question.answers.slice(0, 3);
    this.shuffledAnswers = this.shuffleArray([...limitedAnswers]);
  }

  shuffleArray<T>(array: T[]): T[] {
    const shuffled = [...array];
    for (let i = shuffled.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
  }

  investigateSuspect(answerId: number, index: number): void {
    this.selectedAnswerId = answerId;
    this.answerSelected.emit(answerId);

    if ('vibrate' in navigator) {
      navigator.vibrate(50);
    }
  }

  startInvestigation(index: number): void {
    this.hoveredCard = index;
    this.isHoveringCard = true;
  }

  stopInvestigation(index: number): void {
    this.hoveredCard = null;
    this.isHoveringCard = false;
  }

  makeAccusation(): void {
    if (this.selectedAnswerId && !this.isAccused) {
      this.isAccused = true;
      this.answerValidated.emit();
    }
  }

  isSelected(answerId: number): boolean {
    return this.selectedAnswerId === answerId;
  }

  getAvatarSvg(index: number): string {
    const avatars = [
      '/assets/avatars/blob_flower_blue.svg',
      '/assets/avatars/blob_circle.svg',
      '/assets/avatars/blob_pic_orange.svg',
    ];
    return avatars[index] || avatars[0];
  }

  getValidateText(): string {
    if (this.isAccused) {
      return 'VALIDÉ!';
    } else if (this.selectedAnswerId) {
      return 'VALIDER';
    } else {
      return 'SÉLECTIONNER';
    }
  }

  onMouseMove(event: MouseEvent): void {
    this.cursorPosition.x = event.clientX;
    this.cursorPosition.y = event.clientY;
  }
}
