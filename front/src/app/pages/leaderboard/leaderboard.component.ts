import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';

interface LeaderboardUser {
  id: number;
  pseudo: string;
  firstName: string;
  lastName: string;
  avatar: string;
  avatarShape?: string;
  avatarColor?: string;
  totalScore: number;
  averageScore: number;
  quizzesCompleted: number;
  badgesCount: number;
  rankingScore: number;
  position: number;
  memberSince: string;
  isCurrentUser: boolean;
}

interface LeaderboardResponse {
  leaderboard: LeaderboardUser[];
  currentUser: {
    position: number;
    data: LeaderboardUser | null;
    totalUsers: number;
  };
  meta: {
    totalUsers: number;
    limit: number;
    generatedAt: string;
  };
}

@Component({
  selector: 'app-leaderboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './leaderboard.component.html',
  styleUrls: ['./leaderboard.component.scss']
})
export class LeaderboardComponent implements OnInit {
  private http = inject(HttpClient);

  leaderboardData: LeaderboardResponse | null = null;
  isLoading = true;
  error: string | null = null;

  ngOnInit() {

    this.loadLeaderboard();
  }

  loadLeaderboard() {
    this.isLoading = true;
    this.error = null;

    this.http.get<LeaderboardResponse>(`${environment.apiBaseUrl}/leaderboard?limit=10`)
      .subscribe({
        next: (data) => {
          if (data.leaderboard) {
            const uniqueUsers = new Map();
            data.leaderboard.forEach(user => {
              if (!uniqueUsers.has(user.id)) {
                uniqueUsers.set(user.id, user);
              }
            });
            data.leaderboard = Array.from(uniqueUsers.values());
            data.leaderboard.forEach((user, index) => {
              user.position = index + 1;
            });
          }

          this.leaderboardData = data;
          this.isLoading = false;
        },
        error: (error) => {
          this.error = 'Impossible de charger le classement';
          this.isLoading = false;
        }
      });
  }

  getMedalIcon(position: number): string {
    switch (position) {
      case 1: return 'fas fa-trophy';
      case 2: return 'fas fa-medal';
      case 3: return 'fas fa-award';
      default: return '';
    }
  }

  getMedalClass(position: number): string {
    switch (position) {
      case 1: return 'gold';
      case 2: return 'silver';
      case 3: return 'bronze';
      default: return '';
    }
  }

  getUserAvatarShape(user: LeaderboardUser): string {
    if (user.avatarShape) {
      return user.avatarShape;
    }
    return 'blob_circle';
  }

  getUserAvatarColor(user: LeaderboardUser): string {
    if (user.avatarColor) {
      return user.avatarColor;
    }
    return '#257D54';
  }

  getPositionSuffix(position: number): string {
    if (position >= 11 && position <= 13) return 'ème';
    const lastDigit = position % 10;
    switch (lastDigit) {
      case 1: return 'er';
      default: return 'ème';
    }
  }

  getPlayerLevel(totalScore: number): string {
    if (totalScore >= 1000) return 'Expert';
    if (totalScore >= 500) return 'Avancé';
    if (totalScore >= 200) return 'Intermédiaire';
    if (totalScore >= 50) return 'Débutant+';
    return 'Novice';
  }

  getPlayerLevelClass(totalScore: number): string {
    if (totalScore >= 1000) return 'expert';
    if (totalScore >= 500) return 'advanced';
    if (totalScore >= 200) return 'intermediate';
    if (totalScore >= 50) return 'beginner-plus';
    return 'novice';
  }

  refreshLeaderboard() {
    this.loadLeaderboard();
  }

  trackByPlayerId(index: number, player: LeaderboardUser): number {
    return player.id;
  }

  getHeadAvatarFromShape(shape: string): string {
    if (!shape) {
      return 'head_guest';
    }

    const shapeToHeadMapping: { [key: string]: string } = {
      'blob_flower': 'flower_head',
      'blob_circle': 'circle_head',
      'blob_pic': 'pic_head',
      'blob_wave': 'wave_head'
    };

    const headAvatar = shapeToHeadMapping[shape];
    if (headAvatar) {
      return headAvatar;
    }

    return 'head_guest';
  }
}
