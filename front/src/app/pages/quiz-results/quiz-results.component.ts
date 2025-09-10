import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { SlideButtonComponent } from '../../components/slide-button/slide-button.component';
import { BackButtonComponent } from '../../components/back-button/back-button.component';
import { AuthService } from '../../services/auth.service';

interface QuizResultData {
  quizTitle: string;
  totalScore: number;
  totalQuestions: number;
  playerRank: number;
  totalPlayers: number;
  leaderboard: any[];
  quizId: number;
}

@Component({
  selector: 'app-quiz-results',
  standalone: true,
  imports: [CommonModule, SlideButtonComponent, BackButtonComponent],
  templateUrl: './quiz-results.component.html',
  styleUrls: ['./quiz-results.component.scss'],
})
export class QuizResultsComponent implements OnInit {
  resultData: QuizResultData | null = null;
  isLoading = true;
  Math = Math;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private authService: AuthService
  ) {}

  get isGuest(): boolean {
    return this.authService.isGuest();
  }

  ngOnInit(): void {
    const navigation = this.router.getCurrentNavigation();
    if (navigation?.extras.state) {
      this.resultData = navigation.extras.state as QuizResultData;
      this.isLoading = false;
    } else {
      const savedData = sessionStorage.getItem('quiz-results');
      if (savedData) {
        this.resultData = JSON.parse(savedData);
        this.isLoading = false;
        sessionStorage.removeItem('quiz-results');
      } else {
        this.router.navigate(['/quiz']);
      }
    }
  }

  getScorePercentage(): number {
    if (!this.resultData) return 0;
    return this.resultData.totalScore;
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

  onReplay(): void {
    if (this.resultData) {
      this.router.navigate(['/quiz', this.resultData.quizId, 'play']);
    }
  }

  onShare(): void {
    if (!this.resultData) return;

    const quizUrl = `${window.location.origin}/quiz/${this.resultData.quizId}/play`;
    if (navigator.share) {
      navigator.share({
        title: this.resultData.quizTitle,
        text: `J'ai fait ${this.getScorePercentage()}% à ce quiz !`,
        url: quizUrl,
      });
    } else {
      navigator.clipboard.writeText(quizUrl);
    }
  }

  onBackToQuizzes(): void {
    this.router.navigate(['/quiz']);
  }

  onViewFullLeaderboard(): void {
    if (this.isGuest) {
      this.router.navigate(['/connexion']);
    } else {
      this.router.navigate(['/classement']);
    }
  }
}
