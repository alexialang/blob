import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Question, Answer } from '../../../models/quiz.model';

@Component({
  selector: 'app-blind-test-question',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './blind-test-question.component.html',
  styleUrls: ['./blind-test-question.component.scss'],
})
export class BlindTestQuestionComponent implements OnInit {
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
  isPlaying = false;
  audioUrl: string | null = null;
  currentTime = 0;
  duration = 30;
  intervalId: any;

  ngOnInit(): void {
    this.shuffledAnswers = this.shuffleArray([...this.question.answers]);
    this.audioUrl = '/assets/audio/sample.mp3';
  }

  shuffleArray<T>(array: T[]): T[] {
    const shuffled = [...array];
    for (let i = shuffled.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
  }

  togglePlayPause(): void {
    this.isPlaying = !this.isPlaying;

    if (this.isPlaying) {
      this.startPlayback();
    } else {
      this.stopPlayback();
    }
  }

  startPlayback(): void {
    this.intervalId = setInterval(() => {
      this.currentTime += 0.1;
      if (this.currentTime >= this.duration) {
        this.stopPlayback();
      }
    }, 100);
  }

  stopPlayback(): void {
    this.isPlaying = false;
    if (this.intervalId) {
      clearInterval(this.intervalId);
    }
  }

  resetPlayback(): void {
    this.stopPlayback();
    this.currentTime = 0;
  }

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

  getProgressPercentage(): number {
    return (this.currentTime / this.duration) * 100;
  }

  getFormattedTime(): string {
    const minutes = Math.floor(this.currentTime / 60);
    const seconds = Math.floor(this.currentTime % 60);
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
  }

  getAnswerLetter(index: number): string {
    return String.fromCharCode(65 + index);
  }

  ngOnDestroy(): void {
    this.stopPlayback();
  }
}
