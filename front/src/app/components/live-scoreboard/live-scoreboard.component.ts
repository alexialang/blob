import { Component, Input, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TuiIcon } from '@taiga-ui/core';

export interface LivePlayerScore {
  username: string;
  score: number;
  isCurrentUser: boolean;
  rank: number;
  lastAnswerCorrect?: boolean;
  isOnline: boolean;
  avatar?: string;
  previousRank?: number;
}

@Component({
  selector: 'app-live-scoreboard',
  standalone: true,
  imports: [CommonModule, TuiIcon],
  templateUrl: './live-scoreboard.component.html',
  styleUrls: ['./live-scoreboard.component.scss'],
})
export class LiveScoreboardComponent implements OnChanges {
  @Input() players: LivePlayerScore[] = [];
  @Input() currentQuestionIndex: number = 0;
  @Input() totalQuestions: number = 0;
  @Input() showRanking: boolean = true;

  private blobAvatars = [
    'blob_circle.svg',
    'blob_flower_blue.svg',
    'blob_flower.svg',
    'blob_pic_orange.svg',
    'blob_pic.svg',
    'blob_wave.svg',
    'circle_head.svg',
    'flower_head.svg',
    'pic_head.svg',
    'wave_head.svg',
  ];

  private playerAvatars: { [username: string]: string } = {};
  private previousRanks: { [username: string]: number } = {};

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['players']) {
      this.assignAvatarsToPlayers();
      this.detectRankChanges();
      this.sortPlayersByScore();
    }
  }

  private assignAvatarsToPlayers(): void {
    this.players.forEach(player => {
      if (!this.playerAvatars[player.username]) {
        const availableAvatars = this.blobAvatars.filter(
          avatar => !Object.values(this.playerAvatars).includes(avatar)
        );

        if (availableAvatars.length > 0) {
          const randomIndex = Math.floor(Math.random() * availableAvatars.length);
          this.playerAvatars[player.username] = availableAvatars[randomIndex];
        } else {
          const randomIndex = Math.floor(Math.random() * this.blobAvatars.length);
          this.playerAvatars[player.username] = this.blobAvatars[randomIndex];
        }
      }

      player.avatar = this.playerAvatars[player.username];
    });
  }

  private detectRankChanges(): void {
    this.players.forEach(player => {
      player.previousRank = this.previousRanks[player.username];
    });

    const sortedPlayers = [...this.players].sort((a, b) => {
      if (b.score !== a.score) {
        return b.score - a.score;
      }
      return a.rank - b.rank;
    });

    sortedPlayers.forEach((player, index) => {
      this.previousRanks[player.username] = index + 1;
    });
  }

  private sortPlayersByScore(): void {
    this.players.sort((a, b) => {
      if (b.score !== a.score) {
        return b.score - a.score;
      }
      return a.rank - b.rank;
    });
  }

  getPlayerRank(player: LivePlayerScore): number {
    return this.players.findIndex(p => p.username === player.username) + 1;
  }

  getScorePercentage(player: LivePlayerScore): number {
    if (this.totalQuestions === 0) return 0;

    const percentageScore = Math.round((player.score / (this.totalQuestions * 10)) * 100);
    return Math.min(percentageScore, 100);
  }

  getAvatarPath(player: LivePlayerScore): string {
    return player.avatar ? `/assets/avatars/${player.avatar}` : '/assets/avatars/blob_circle.svg';
  }

  hasRankChanged(player: LivePlayerScore): boolean {
    const currentRank = this.getPlayerRank(player);
    return player.previousRank !== undefined && player.previousRank !== currentRank;
  }

  getRankChange(player: LivePlayerScore): 'up' | 'down' | 'same' {
    const currentRank = this.getPlayerRank(player);
    if (player.previousRank === undefined || player.previousRank === currentRank) {
      return 'same';
    }
    return player.previousRank > currentRank ? 'up' : 'down';
  }
}
