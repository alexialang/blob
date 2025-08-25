import { Component, Input, OnInit, OnDestroy, OnChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TuiIcon } from '@taiga-ui/core';

export interface TransitionPlayer {
  id: number;
  username: string;
  email?: string;
  avatar?: {
    shape: string;
    color: string;
  };
  score: number;
  rank: number;
  isCurrentUser: boolean;
  lastAnswerCorrect?: boolean;
  scorePercentage: number;
  pointsGained?: number;
}

@Component({
  selector: 'app-multiplayer-transition',
  standalone: true,
  imports: [CommonModule, TuiIcon],
  templateUrl: './multiplayer-transition.component.html',
  styleUrls: ['./multiplayer-transition.component.scss']
})
export class MultiplayerTransitionComponent implements OnInit, OnDestroy, OnChanges {
  @Input() players: TransitionPlayer[] = [];
  @Input() questionNumber: number = 1;
  @Input() totalQuestions: number = 1;
  @Input() show: boolean = false;
  @Input() duration: number = 6000;

  private hideTimeout?: number;

  ngOnInit(): void {
    if (this.show) {
      this.showTransition();
    }
  }

  ngOnDestroy(): void {
    if (this.hideTimeout) {
      clearTimeout(this.hideTimeout);
    }
  }

  ngOnChanges(): void {
    if (this.show) {
      this.showTransition();
    }
  }

  private showTransition(): void {
    this.players.sort((a, b) => b.score - a.score);

    this.players.forEach((player, index) => {
      player.rank = index + 1;
    });

    this.hideTimeout = window.setTimeout(() => {
      this.show = false;
    }, this.duration);
  }

  getRankMedal(rank: number): string {
    switch (rank) {
      case 1: return '🥇';
      case 2: return '🥈';
      case 3: return '🥉';
      default: return '';
    }
  }

  getRankColor(rank: number): string {
    switch (rank) {
      case 1: return 'var(--color-accent)';
      case 2: return '#c0c0c0';
      case 3: return '#cd7f32';
      default: return 'var(--color-text-secondary)';
    }
  }

  getPlayerCardClass(player: TransitionPlayer): string {
    let classes = 'player-card';

    if (player.isCurrentUser) {
      classes += ' current-user';
    }

    if (player.rank <= 3) {
      classes += ` rank-${player.rank}`;
    }

    return classes;
  }

  getProgressPercentage(): number {
    return Math.round((this.questionNumber / this.totalQuestions) * 100);
  }

  getAvatarStyle(player: TransitionPlayer): any {
    if (!player.avatar) {
      return {
        'background': 'linear-gradient(135deg, var(--color-secondary), var(--color-secondary-dark))'
      };
    }

    return {
      'background': `linear-gradient(135deg, ${player.avatar.color}, ${this.getDarkerColor(player.avatar.color)})`
    };
  }

  private getDarkerColor(color: string): string {
    if (color === '#91DEDA') return '#5BC0BE';
    if (color === '#FAA24B') return '#E67E22';
    if (color === '#D30D4C') return '#B91C5C';
    return color;
  }

  getAvatarShape(player: TransitionPlayer): string {
    return player.avatar?.shape || 'blob_circle';
  }
}

