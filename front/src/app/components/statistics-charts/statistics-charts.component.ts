import {
  Component,
  Input,
  OnInit,
  OnDestroy,
  ElementRef,
  ViewChild,
  AfterViewInit,
  OnChanges,
} from '@angular/core';
import { CommonModule } from '@angular/common';

declare const Chart: any;

@Component({
  selector: 'app-statistics-charts',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './statistics-charts.component.html',
  styleUrls: ['./statistics-charts.component.scss'],
})
export class StatisticsChartsComponent implements OnInit, AfterViewInit, OnDestroy, OnChanges {
  @Input() userStatistics: any = null;

  @ViewChild('scoreEvolutionChart', { static: false }) scoreChart!: ElementRef<HTMLCanvasElement>;
  @ViewChild('categoryChart', { static: false }) categoryChart!: ElementRef<HTMLCanvasElement>;

  private scoreEvolutionChartInstance: any;
  private categoryChartInstance: any;
  private chartsCreated = false;

  ngOnInit() {}

  ngAfterViewInit() {
    this.loadChartJs().then(() => {
      setTimeout(() => {
        if (this.userStatistics && this.scoreChart && this.categoryChart && !this.chartsCreated) {
          this.createCharts();
          this.chartsCreated = true;
        }
      }, 100);
    });
  }

  ngOnDestroy() {
    if (this.scoreEvolutionChartInstance) {
      this.scoreEvolutionChartInstance.destroy();
    }
    if (this.categoryChartInstance) {
      this.categoryChartInstance.destroy();
    }
    this.chartsCreated = false;
  }

  private async loadChartJs(): Promise<void> {
    if (typeof Chart === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
      document.head.appendChild(script);

      return new Promise(resolve => {
        script.onload = () => resolve();
      });
    }
    return Promise.resolve();
  }

  ngOnChanges() {
    if (
      this.userStatistics &&
      typeof Chart !== 'undefined' &&
      this.scoreChart &&
      this.categoryChart
    ) {
      if (this.scoreEvolutionChartInstance) {
        this.scoreEvolutionChartInstance.destroy();
        this.scoreEvolutionChartInstance = null;
      }
      if (this.categoryChartInstance) {
        this.categoryChartInstance.destroy();
        this.categoryChartInstance = null;
      }

      this.createCharts();
      this.chartsCreated = true;
    }
  }

  private createCharts() {
    this.createScoreEvolutionChart();
    this.createCategoryPerformanceChart();
  }

  private createScoreEvolutionChart() {
    if (!this.scoreChart || !this.userStatistics?.scoreHistory) return;

    const ctx = this.scoreChart.nativeElement.getContext('2d');
    if (this.scoreEvolutionChartInstance) {
      this.scoreEvolutionChartInstance.destroy();
    }

    const scoreHistory = this.userStatistics.scoreHistory || [];
    const labels = scoreHistory.map((item: any) => this.formatDate(item.date));
    const scores = scoreHistory.map((item: any) => item.score);

    this.scoreEvolutionChartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Score obtenu',
            data: scores,
            borderColor: 'rgb(102, 126, 234)',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgb(102, 126, 234)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Évolution de vos scores',
            font: {
              size: 16,
              weight: 'bold',
            },
            color: '#333',
            padding: {
              top: 10,
              bottom: 20,
            },
          },
          legend: {
            display: false,
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgb(102, 126, 234)',
            borderWidth: 1,
            cornerRadius: 8,
            callbacks: {
              afterLabel: (context: any) => {
                const quiz = scoreHistory[context.dataIndex];
                return quiz ? `Quiz: ${quiz.quizTitle}` : '';
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
              color: '#666',
            },
          },
          x: {
            grid: {
              color: 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
              color: '#666',
            },
          },
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
      },
    });
  }

  private createCategoryPerformanceChart() {
    if (!this.categoryChart || !this.userStatistics?.categoryPerformance) return;

    const ctx = this.categoryChart.nativeElement.getContext('2d');
    if (this.categoryChartInstance) {
      this.categoryChartInstance.destroy();
    }

    const categoryData = this.userStatistics.categoryPerformance || [];
    const labels = categoryData.map((item: any) => item.category);
    const averages = categoryData.map((item: any) => item.average);
    const colors = [
      'rgba(102, 126, 234, 0.8)',
      'rgba(76, 175, 80, 0.8)',
      'rgba(255, 193, 7, 0.8)',
      'rgba(233, 30, 99, 0.8)',
      'rgba(255, 87, 34, 0.8)',
      'rgba(156, 39, 176, 0.8)',
    ];

    this.categoryChartInstance = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [
          {
            data: averages,
            backgroundColor: colors.slice(0, labels.length),
            borderColor: colors.slice(0, labels.length).map(color => color.replace('0.8', '1')),
            borderWidth: 2,
            hoverBorderWidth: 3,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Performance par catégorie',
            font: {
              size: 16,
              weight: 'bold',
            },
            color: '#333',
            padding: {
              top: 10,
              bottom: 20,
            },
          },
          legend: {
            position: 'bottom',
            labels: {
              padding: 20,
              usePointStyle: true,
              color: '#666',
            },
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgb(102, 126, 234)',
            borderWidth: 1,
            cornerRadius: 8,
            callbacks: {
              label: (context: any) => {
                const category = categoryData[context.dataIndex];
                return `${context.label}: ${context.parsed} pts (${category.count} quiz)`;
              },
            },
          },
        },
      },
    });
  }

  private formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: '2-digit',
    });
  }

  hasScoreHistory(): boolean {
    return this.userStatistics?.scoreHistory && this.userStatistics.scoreHistory.length > 0;
  }

  hasCategoryData(): boolean {
    return (
      this.userStatistics?.categoryPerformance && this.userStatistics.categoryPerformance.length > 0
    );
  }

  getProgressTrend(): number {
    if (!this.hasScoreHistory() || this.userStatistics.scoreHistory.length < 2) {
      return 0;
    }

    const scores = this.userStatistics.scoreHistory;
    const recentScores = scores.slice(-3);
    const olderScores = scores.slice(0, -3);

    if (olderScores.length === 0) return 0;

    const recentAvg =
      recentScores.reduce((sum: number, item: any) => sum + item.score, 0) / recentScores.length;
    const olderAvg =
      olderScores.reduce((sum: number, item: any) => sum + item.score, 0) / olderScores.length;

    return Math.round(((recentAvg - olderAvg) / olderAvg) * 100);
  }

  getRecommendedTarget(): number {
    if (!this.hasScoreHistory()) {
      return 80;
    }

    const scores = this.userStatistics.scoreHistory.map((item: any) => item.score);
    const averageScore =
      scores.reduce((sum: number, score: number) => sum + score, 0) / scores.length;
    const maxScore = Math.max(...scores);
    return Math.min(100, Math.round(Math.max(averageScore + 10, maxScore + 5)));
  }
}
