import { Component, Input, Output, EventEmitter, ElementRef, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { QuizCard } from '../../models/quiz.model';
import { QuizTransitionService } from '../../services/quiz-transition.service';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-quiz-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './quiz-card.component.html',
  styleUrls: ['./quiz-card.component.scss'],
})
export class QuizCardComponent implements OnInit {
  @Input() quiz!: QuizCard;
  @Input() index: number = 0;
  @Input() rowIndex: number = 0;
  @Output() quizStart = new EventEmitter<QuizCard>();
  @Output() quizFlip = new EventEmitter<QuizCard>();
  @Output() playModeChange = new EventEmitter<QuizCard>();

  cardColor: string = '';
  cardTextColor: string = '';

  private readonly colorPalette = [
    { bg: 'var(--color-primary)', text: 'white' },
    { bg: 'var(--color-secondary-dark)', text: 'white' },
    { bg: 'var(--color-accent-dark)', text: 'white' },
    { bg: 'var(--color-pink-dark)', text: 'white' },
  ];

  constructor(
    private quizTransitionService: QuizTransitionService,
    private elementRef: ElementRef,
    private authService: AuthService
  ) {}

  ngOnInit() {
    this.assignBalancedColor();
  }

  private assignBalancedColor() {
    const shuffledColors = this.getShuffledColorsForRow(this.rowIndex);
    const colorIndex = this.index % this.colorPalette.length;
    const selectedColor = shuffledColors[colorIndex];
    this.cardColor = selectedColor.bg;
    this.cardTextColor = selectedColor.text;
  }

  private getShuffledColorsForRow(rowIndex: number) {
    const predefinedOrders = [
      [0, 1, 2, 3],
      [2, 0, 3, 1],
      [3, 2, 1, 0],
      [1, 3, 0, 2],
      [2, 3, 0, 1],
      [0, 2, 1, 3],
      [1, 0, 3, 2],
      [3, 1, 2, 0],
    ];

    const orderIndex = rowIndex % predefinedOrders.length;
    const order = predefinedOrders[orderIndex];

    return order.map(index => this.colorPalette[index]);
  }

  get isGuest(): boolean {
    return this.authService.isGuest();
  }

  async startQuiz() {
    if (this.quiz.playMode === 'team') {
      this.quizStart.emit(this.quiz);
      return;
    }

    const cardElement = this.elementRef.nativeElement.querySelector('.quiz-card');
    await this.quizTransitionService.startTransition(this.quiz, cardElement, this.cardColor);
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
