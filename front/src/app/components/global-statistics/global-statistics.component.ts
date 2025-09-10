import { Component, OnInit, AfterViewInit, OnDestroy, ElementRef, ViewChild, OnChanges, SimpleChanges, Input, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { GlobalStatisticsService } from '../../services/global-statistics.service';

declare const Chart: any;

interface QuizScore {
  quizTitle: string;
  quizId: number;
  averageScore: number;
  participants: number;
}

interface GroupScores {
  [groupName: string]: QuizScore[];
}

interface GlobalStats {
  teamScores: QuizScore[];
  groupScores: GroupScores;
}

@Component({
  selector: 'app-global-statistics',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './global-statistics.component.html',
  styleUrls: ['./global-statistics.component.scss']
})
export class GlobalStatisticsComponent implements OnInit, AfterViewInit, OnDestroy, OnChanges {
  @ViewChild('statsChart', { static: false }) chartElement!: ElementRef<HTMLCanvasElement>;
  @Input() companyId: number | null = null;

  stats: GlobalStats | null = null;
  loading = true;
  error: any = null;
  private chart: any = null;
  private chartJsLoaded = false;
  private retryCount = 0;
  quizFilter: string = '';

  constructor(private globalStatsService: GlobalStatisticsService, private cdr: ChangeDetectorRef) {}

  ngOnInit(): void {
    this.loadStats();
  }

  ngAfterViewInit(): void {
    this.loadChartJs().then(() => {
      this.chartJsLoaded = true;
      this.tryCreateChart();
    });
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['companyId'] && !changes['companyId'].firstChange) {
      this.loadStats();
    }

    if (changes['loading'] && !changes['loading'].currentValue && this.chartJsLoaded) {
      setTimeout(() => this.tryCreateChart(), 100);
    }
  }

  ngOnDestroy(): void {
    if (this.chart) {
      this.chart.destroy();
    }
  }

  trackByGroup(index: number, groupEntry: { key: string; value: QuizScore[] }): string {
    return groupEntry.key;
  }

  getGroupQuizCount(groupName: string): number {
    return this.stats?.groupScores?.[groupName]?.length || 0;
  }

  getGroupQuizzes(groupName: string): QuizScore[] {
    return this.stats?.groupScores?.[groupName] || [];
  }

  forceChartDisplay(): void {
    this.cdr.detectChanges();

    if (this.stats && !this.loading) {
      this.tryCreateChart();
    }

    setTimeout(() => {
      this.cdr.detectChanges();
    }, 200);
  }

  refreshStats(): void {
    if (this.loading) {
      return;
    }

    this.loading = true;
    this.stats = null;
    this.error = null;
    this.quizFilter = '';

    if (this.chart) {
      this.chart.destroy();
      this.chart = null;
    }

    this.cdr.detectChanges();

    setTimeout(() => {
      this.loadStats();

      setTimeout(() => {
        this.forceChartDisplay();
      }, 150);
    }, 100);
  }

  onQuizFilterChange(): void {
    if (this.stats && !this.loading) {
      this.tryCreateChart();
    }
  }

  getFilteredTeamScores(): QuizScore[] {
    if (!this.stats?.teamScores) return [];

    if (!this.quizFilter.trim()) {
      return this.stats.teamScores;
    }

    const filter = this.quizFilter.toLowerCase();
    return this.stats.teamScores.filter(quiz =>
      quiz.quizTitle.toLowerCase().includes(filter)
    );
  }

  getTotalGroupQuizzes(): number {
    if (!this.stats?.groupScores) return 0;

    let total = 0;
    Object.values(this.stats.groupScores).forEach(groupQuizzes => {
      if (Array.isArray(groupQuizzes)) {
        total += groupQuizzes.length;
      }
    });
    return total;
  }

  private async loadChartJs(): Promise<void> {
    if (typeof Chart === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
      document.head.appendChild(script);

      return new Promise((resolve) => {
        script.onload = () => {
          resolve();
        };
        script.onerror = () => {
          resolve();
        };
      });
    }
    return Promise.resolve();
  }

  private createChart(): void {
    let canvas: HTMLCanvasElement | null = null;

    if (this.chartElement?.nativeElement) {
      canvas = this.chartElement.nativeElement;
    } else {
      canvas = document.querySelector('#statsChart') as HTMLCanvasElement;
    }

    if (!canvas) {
      return;
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) {
      return;
    }

    if (this.chart) {
      this.chart.destroy();
    }

    const teamScoresFiltered = this.getFilteredTeamScores();

    const allQuizTitles = new Set<string>();

    teamScoresFiltered.forEach(quiz => allQuizTitles.add(quiz.quizTitle));

    Object.values(this.stats?.groupScores || {}).forEach(groupQuizzes => {
      if (Array.isArray(groupQuizzes)) {
        groupQuizzes.forEach(quiz => allQuizTitles.add(quiz.quizTitle));
      }
    });

    const unifiedLabels = Array.from(allQuizTitles).sort();

    const teamData = unifiedLabels.map(label => {
      const teamQuiz = teamScoresFiltered.find(q => q.quizTitle === label);
      return teamQuiz ? teamQuiz.averageScore : null;
    });

    const groupNames = Object.keys(this.stats?.groupScores || {});
    const groupDatasets = groupNames.map((groupName, groupIndex) => {
      const groupData = this.stats?.groupScores?.[groupName];
      if (!groupData) return null;

      const scores = unifiedLabels.map(label => {
        const groupQuiz = groupData.find(q => q.quizTitle === label);
        if (groupQuiz) {
          return groupQuiz.averageScore;
        }

        return null;
      });

      return {
        label: `Groupe ${groupName}`,
        data: scores,
        borderColor: this.getGroupColor(groupIndex),
        backgroundColor: this.getGroupColor(groupIndex) + '20',
        tension: 0.4,
        fill: false,
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 2,
        pointHoverBackgroundColor: this.getGroupColor(groupIndex),
        pointHoverBorderColor: '#fff'
      };
    }).filter(dataset => dataset !== null);

    try {
      this.chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: unifiedLabels,
          datasets: [
            {
              label: 'Équipe (moyenne)',
              data: teamData,
              borderColor: '#2c3e50',
              backgroundColor: '#2c3e5020',
              borderWidth: 3,
              tension: 0.4,
              fill: false,
              pointRadius: 6,
              pointHoverRadius: 8
            },
            ...groupDatasets
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          aspectRatio: 3,
          plugins: {
            title: {
              display: true,
              text: 'Évolution des scores moyens par quiz',
              font: { size: 18, weight: 'bold' },
              color: '#2c3e50',
              padding: {
                top: 10,
                bottom: 20
              }
            },
            legend: {
              position: 'top',
              labels: {
                usePointStyle: true,
                padding: 20,
                font: { size: 14 }
              }
            },
            tooltip: {
              callbacks: {
                label: function(context: any) {
                  return `${context.dataset.label}: ${context.parsed.y}/100`;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              max: 100,
              min: -5,
              title: {
                display: true,
                text: 'Score moyen (/100)',
                font: { size: 14, weight: 'bold' }
              },
              ticks: {
                stepSize: 20,
                font: { size: 12 },
                callback: function(value: any) {
                  if (value < 0) return '';
                  return value + '/100';
                }
              },
              grid: {
                color: 'rgba(0,0,0,0.1)'
              }
            },
            x: {
              title: {
                display: true,
                text: 'Quiz',
                font: { size: 14, weight: 'bold' }
              },
              ticks: {
                font: { size: 12 },
                maxRotation: 45
              },
              grid: {
                color: 'rgba(0,0,0,0.1)'
              }
            }
          },
          interaction: {
            intersect: false,
            mode: 'index'
          },
          elements: {
            point: {
              hoverBackgroundColor: '#fff',
              hoverBorderColor: '#2c3e50'
            }
          },
          layout: {
            padding: {
              top: 40,
              right: 40,
              bottom: 120,
              left: 40
            }
          }
        }
      });

    } catch (error) {
    }
  }

  private getGroupColor(index: number): string {
    const colors = [
      '#3498db', '#e74c3c', '#2ecc71', '#f39c12',
      '#9b59b6', '#1abc9c', '#e67e22', '#34495e'
    ];
    return colors[index % colors.length];
  }

  private loadStats(): void {
    if (!this.companyId) {
      this.globalStatsService.getGlobalStatistics().subscribe({
        next: (data: any) => {
          this.stats = {
            teamScores: data.teamScores || [],
            groupScores: data.groupScores || {}
          };
          this.loading = false;
          this.tryCreateChart();
        },
        error: (error) => {
          this.loading = false;
          this.error = error;
          if (error.status === 401) {
          } else if (error.status === 403) {
          } else if (error.status === 500) {
          }
        }
      });
    } else {
      this.globalStatsService.getCompanyStatistics(this.companyId).subscribe({
        next: (data: any) => {
          // Normaliser les données groupScores pour les statistiques d'entreprise
          let normalizedGroupScores = data.groupScores || {};

          // Si groupScores est un tableau (cas des statistiques d'entreprise),
          // on le convertit en objet avec une clé par défaut
          if (Array.isArray(data.groupScores)) {
            normalizedGroupScores = { 'Entreprise': data.groupScores };
          }

          this.stats = {
            teamScores: data.teamScores || [],
            groupScores: normalizedGroupScores
          };
          this.loading = false;
          this.tryCreateChart();
        },
        error: (error) => {
          this.loading = false;
          this.error = error;
          if (error.status === 401) {
          } else if (error.status === 403) {
          } else if (error.status === 500) {
          }
        }
      });
    }
  }

  private tryCreateChart(): void {
    if (!this.stats || this.loading) {
      return;
    }

    let canvas: HTMLCanvasElement | null = null;

    if (this.chartElement?.nativeElement) {
      canvas = this.chartElement.nativeElement;
    } else {
      canvas = document.querySelector('#statsChart') as HTMLCanvasElement;
    }

    if (canvas) {
      this.createChart();

      setTimeout(() => {
        this.cdr.detectChanges();
      }, 50);
    } else {
      if (this.retryCount < 10) {
        this.retryCount++;
        setTimeout(() => this.tryCreateChart(), 100);
      } else {
        this.retryCount = 0;
      }
    }
  }
}
