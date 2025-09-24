import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { SlideButtonComponent } from '../slide-button/slide-button.component';

@Component({
  selector: 'app-leaderboard-modal',
  standalone: true,
  imports: [CommonModule, SlideButtonComponent],
  templateUrl: './leaderboard-modal.component.html',
  styleUrls: ['./leaderboard-modal.component.scss'],
})
export class LeaderboardModalComponent {
  @Input() isVisible = false;
  @Input() leaderboard: any[] = [];
  @Input() quizTitle = '';
  @Input() playerRank = 1;
  @Input() totalScore = 0;
  @Input() totalPlayers = 1;

  @Output() close = new EventEmitter<void>();
  @Output() replay = new EventEmitter<void>();
  @Output() share = new EventEmitter<void>();

  closeModal(): void {
    this.close.emit();
  }

  onReplay(): void {
    this.replay.emit();
  }

  onShare(): void {
    this.share.emit();
  }

  getMedalIcon(position: number): string {
    switch (position) {
      case 1:
        return '🥇';
      case 2:
        return '🥈';
      case 3:
        return '🥉';
      default:
        return '';
    }
  }

  getPlayerLevel(score: number): string {
    if (score >= 80) return 'Expert';
    if (score >= 60) return 'Avancé';
    if (score >= 40) return 'Intermédiaire';
    if (score >= 20) return 'Débutant';
    return 'Novice';
  }

  getPositionSuffix(position: number): string {
    if (position >= 11 && position <= 13) return 'ème';
    const lastDigit = position % 10;
    switch (lastDigit) {
      case 1:
        return 'er';
      default:
        return 'ème';
    }
  }
}
