import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-quiz-leaderboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './quiz-leaderboard.component.html',
  styleUrl: './quiz-leaderboard.component.scss'
})
export class QuizLeaderboardComponent {
  @Input() leaderboard: any[] = [];
  @Input() quizTitle = '';
  @Input() playerRank = 1;
  @Input() totalScore = 0;
  @Input() totalPlayers = 0;

  @Output() close = new EventEmitter<void>();
  @Output() replay = new EventEmitter<void>();
  @Output() share = new EventEmitter<void>();

  onClose() {
    this.close.emit();
  }

  onReplay() {
    this.replay.emit();
  }

  onShare() {
    this.share.emit();
  }
}
