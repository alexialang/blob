import {Component, OnDestroy, OnInit} from '@angular/core';
import {CommonModule} from '@angular/common';
import {ActivatedRoute, Router} from '@angular/router';
import {MultiplayerGame, MultiplayerService} from '../../services/multiplayer.service';
import {MercureService} from '../../services/mercure.service';
import {QuizGameService} from '../../services/quiz-game.service';
import {Subscription} from 'rxjs';
import {animate, state, style, transition, trigger} from '@angular/animations';
import {McqQuestionComponent} from '../../components/question-types/mcq-question/mcq-question.component';
import {
  MultipleChoiceQuestionComponent
} from '../../components/question-types/multiple-choice-question/multiple-choice-question.component';
import {
  RightOrderQuestionComponent
} from '../../components/question-types/right-order-question/right-order-question.component';
import {
  TrueFalseQuestionComponent
} from '../../components/question-types/true-false-question/true-false-question.component';
import {MatchingQuestionComponent} from '../../components/question-types/matching-question/matching-question.component';
import {IntruderQuestionComponent} from '../../components/question-types/intruder-question/intruder-question.component';
import {
  BlindTestQuestionComponent
} from '../../components/question-types/blind-test-question/blind-test-question.component';
import {BackButtonComponent} from '../../components/back-button/back-button.component';
import {SlideButtonComponent} from '../../components/slide-button/slide-button.component';
import {LivePlayerScore, LiveScoreboardComponent} from '../../components/live-scoreboard/live-scoreboard.component';
import {
  MultiplayerTransitionComponent,
  TransitionPlayer
} from '../../components/multiplayer-transition/multiplayer-transition.component';

import {TuiIcon} from '@taiga-ui/core';

@Component({
  selector: 'app-multiplayer-game',
  standalone: true,
  imports: [
    CommonModule,
    McqQuestionComponent,
    MultipleChoiceQuestionComponent,
    RightOrderQuestionComponent,
    TrueFalseQuestionComponent,
    MatchingQuestionComponent,
    IntruderQuestionComponent,
    BlindTestQuestionComponent,
    BackButtonComponent,
    SlideButtonComponent,
    LiveScoreboardComponent,
    MultiplayerTransitionComponent,

    TuiIcon
  ],
  templateUrl: './multiplayer-game.component.html',
  styleUrls: ['./multiplayer-game.component.scss'],
  animations: [
    trigger('slideFeedback', [
      state('in', style({transform: 'translateX(0)'})),
      transition('void => *', [
        style({transform: 'translateX(-100%)'}),
        animate(300)
      ]),
      transition('* => void', [
        animate(300, style({transform: 'translateX(100%)'}))
      ])
    ])
  ]
})
export class MultiplayerGameComponent implements OnInit, OnDestroy {
  currentGame: MultiplayerGame | null = null;
  currentQuestion: any = null;
  questions: any[] = [];
  currentQuestionIndex = 0;
  totalQuestions = 0;

  quizCompleted = false;
  showFeedback = false;
  overlayCorrect = false;
  showCorrectAnswer = true;
  correctAnswers: any[] = [];
  playerRank = 1;
  totalPlayers = 2;
  userRating = 0;
  hoverRating = 0;

  currentScore = 0;
  timeLeft = 30;
  private timer: any;


  private serverTimeOffset = 0;
  private questionStartTimestamp = 0;
  private questionDuration = 30;


  livePlayers: LivePlayerScore[] = [];

  lastAnswerResults: { [username: string]: boolean } = {};
  questionPointsGained: { [username: string]: number } = {};

  showTransition = false;
  transitionPlayers: TransitionPlayer[] = [];
  transitionDuration = 3000;

  tempPlayerScore: { username: string; points: number; isCorrect: boolean } | null = null;

  selectedAnswer: number | null = null;
  selectedAnswers: number[] = [];

  currentUser: any = null;
  playersAnswered: string[] = [];
  finalLeaderboard: any[] = [];

  hasAnswered = false;
  questionStartTime = 0;
  gameCompleted = false;
  lastAnswerCorrect = false;
  waitingForPlayers = false;

  private subscriptions: Subscription[] = [];
  private gameId: string = '';

  private isSyncing = false;
  private syncInterval: any = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private multiplayerService: MultiplayerService,
    private mercureService: MercureService,
    private quizGameService: QuizGameService
  ) {}

  ngOnInit(): void {
    this.gameId = this.route.snapshot.params['id'];

    this.preventNavigation();

    this.mercureService.connectWithGame(this.gameId);
    this.setupGameSubscriptions();
    this.setupLocalStorageSync();
    this.loadGameState();
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
    this.mercureService.disconnectFromGame();
    this.stopTimer();

    if (this.syncInterval) {
      clearInterval(this.syncInterval);
      this.syncInterval = null;
    }
  }

  private setupGameSubscriptions(): void {
    const gameEventSub = this.mercureService.gameSync$.subscribe(event => {
      this.handleGameEvent(event);
    });

    const generalEventSub = this.mercureService.gameEvent$.subscribe(event => {
      this.handleGameEvent(event);
    });

    this.subscriptions.push(gameEventSub, generalEventSub);
  }

  private handleGameEvent(event: any): void {
    switch (event.action) {
      case 'new_question':
        if (event.questionStartTime && event.questionDuration) {
          this.questionStartTimestamp = event.questionStartTime;
          this.questionDuration = event.questionDuration;
          this.timeLeft = event.timeLeft || 30;
          console.log(`Nouvelle question - Début: ${new Date(event.questionStartTime)}, Durée: ${event.questionDuration}s`);
        }
        this.startNewQuestion(event.questionIndex, event.question, event.timeLeft);
        break;
      case 'answer_submitted':
        this.onPlayerAnswered(event.username);
        break;
      case 'player_answered':
        if (event.username) {
          this.onPlayerAnswered(event.username);
        }
        break;
      case 'show_feedback':
        this.showFeedbackPhase(event.leaderboard);
        break;
      case 'game_finished':
        this.showFinalResults(event.leaderboard);
        break;
      case 'timer_update':
        this.timeLeft = event.timeLeft;
        console.log(` TIMER_UPDATE reçu: ${event.timeLeft}s - Event complet:`, event);
        break;
      case 'time_expired':
        console.log('ÉVÉNEMENT TEMPS_ECOULE reçu du serveur');
        this.handleTimeExpiredFromServer(event.leaderboard);
        break;
      case 'score_update':
        this.handleScoreUpdate(event);
        break;
    }
  }

  private loadGameState(): void {
    this.multiplayerService.getGameStatus(this.gameId).subscribe({
      next: (game) => {
        this.currentGame = game;
        this.totalPlayers = game.players.length;

        if (game.timeLeft !== undefined) {
          this.timeLeft = game.timeLeft;
          console.log(`Temps restant récupéré depuis le serveur: ${this.timeLeft}s`);
        }

        if (game.questionStartTime && game.questionDuration) {
          this.questionStartTimestamp = game.questionStartTime;
          this.questionDuration = game.questionDuration;
          console.log(`Question démarrée à: ${new Date(game.questionStartTime)}`);
          console.log(`Durée: ${game.questionDuration}s`);
        }

        const currentUsername = this.getCurrentUsername();
        this.currentUser = this.findCurrentUser(game.players, currentUsername);

        this.initializeLiveScoreboard(game);

        this.calculateServerTimeOffset();

        this.quizGameService.loadQuiz(game.quiz.id).subscribe({
          next: (quiz) => {
            this.questions = quiz.questions;
            this.totalQuestions = quiz.questions.length;

            if (game.currentQuestionIndex >= 0 && quiz.questions[game.currentQuestionIndex]) {
              this.loadQuestionAtIndex(game.currentQuestionIndex);
            }
          },
          error: (error) => {
            console.error('Erreur chargement quiz:', error);
          }
        });
      },
      error: (error) => {
        console.error('Erreur chargement jeu:', error);
        this.router.navigate(['/quiz']);
      }
    });
  }

  private initializeLiveScoreboard(game: MultiplayerGame): void {
    this.livePlayers = game.players.map(player => {
      let playerScore = 0;

      if (game.playerScores && game.playerScores[player.id] !== undefined) {
        playerScore = game.playerScores[player.id];
      }
      else if (game.sharedScores && game.sharedScores[player.username] !== undefined) {
        playerScore = game.sharedScores[player.username];
      }
      else if (game.leaderboard) {
        const leaderboardEntry = game.leaderboard.find((entry: any) => entry.username === player.username);
        if (leaderboardEntry) {
          playerScore = leaderboardEntry.score || 0;
        }
      }


      return {
        username: player.username,
        score: playerScore,
        isCurrentUser: player.username === this.getCurrentUsername(),
        rank: 1,
        isOnline: true,
        lastAnswerCorrect: undefined
      };
    });

    const currentUsername = this.getCurrentUsername();
    if (currentUsername) {
      const currentPlayer = this.livePlayers.find(p => p.username === currentUsername);
      if (currentPlayer) {
        this.currentScore = currentPlayer.score;
      }
    }
    this.updateLiveScoreboard();
  }

  private calculateServerTimeOffset(): void {

    const serverTime = this.currentGame?.startedAt || Date.now();
    this.serverTimeOffset = serverTime - Date.now();
  }

  private updateLiveScoreboard(): void {
    this.livePlayers.sort((a, b) => b.score - a.score);

    this.livePlayers.forEach((player, index) => {
      player.rank = index + 1;
    });
  }



  private handleScoreUpdate(event: any): void {
    if (event.username && event.score !== undefined) {
      const player = this.livePlayers.find(p => p.username === event.username);
      if (player) {
        player.score = event.score;
        this.updateLiveScoreboard();
      }
    }
  }

  private updatePlayerScore(username: string | undefined, isCorrect: boolean, points: number): void {
    if (!username) return;

    const player = this.livePlayers.find(p => p.username === username);
    if (player) {
      player.score += points;
      player.lastAnswerCorrect = isCorrect;
      this.lastAnswerResults[username] = isCorrect;
      this.updateLiveScoreboard();
    }
  }



  private loadQuestionAtIndex(index: number): void {
    if (!this.questions || !this.questions[index]) return;

    this.currentQuestion = this.questions[index];
    this.currentQuestionIndex = index + 1;
    this.showFeedback = false;
    this.questionStartTime = Date.now();
    this.resetAnswers();

    this.questionDuration = 30;
    this.timeLeft = 30;
    this.questionStartTimestamp = Date.now() + this.serverTimeOffset;
    this.startTimer();
  }

  private startNewQuestion(questionIndex: number, question: any, timeLeft: number): void {
    this.showFeedback = false;
    this.playersAnswered = [];
    this.questionStartTime = Date.now();
    this.resetAnswers();

    this.lastAnswerResults = {};
    this.livePlayers.forEach(player => {
      player.lastAnswerCorrect = undefined;
    });

    if (this.questions && this.questions[questionIndex]) {
      this.loadQuestionAtIndex(questionIndex);
    } else {
      this.currentQuestionIndex = questionIndex + 1;
      this.currentQuestion = question;

      this.questionDuration = timeLeft;
      this.timeLeft = timeLeft;
      this.questionStartTimestamp = Date.now() + this.serverTimeOffset;
      this.startTimer();
    }
  }

  private startTimer(): void {
    this.stopTimer();


    this.timer = setInterval(() => {

      if (this.timeLeft > 0) {
        this.timeLeft--;
        console.log(`Timer local: ${this.timeLeft}s restant`);
      }

      if (this.timeLeft <= 0) {
        this.stopTimer();
        this.handleTimeExpired();
      }
    }, 1000);

    console.log('Timer hybride activé - Local + Synchronisation serveur');
  }


  private handleTimeExpired(): void {
    if (!this.hasAnswered) {
      console.log(' Temps écoulé - Traitement comme mauvaise réponse');

      this.hasAnswered = true;
      this.waitingForPlayers = false;

      this.lastAnswerCorrect = false;
      this.overlayCorrect = false;

      this.tempPlayerScore = {
        username: this.currentUser?.username || '',
        points: 0,
        isCorrect: false
      };

      const currentPlayer = this.livePlayers.find(p => p.username === this.currentUser?.username);
      if (currentPlayer) {
        currentPlayer.lastAnswerCorrect = false;
        this.updateLiveScoreboard();
      }

      this.saveAllScoresToLocalStorage();

      if (this.currentUser?.username) {
        this.onPlayerAnswered(this.currentUser.username);
      }

      const tempLeaderboard = this.livePlayers.map(player => ({
        username: player.username,
        score: player.score,
        rank: 1
      }));

      this.showFeedbackPhase(tempLeaderboard);

    } else if (this.hasAnswered) {
      this.forceFeedbackDueToTimeout();
    }
  }


  private handleTimeExpiredFromServer(leaderboard: any[]): void {
    console.log(' Temps écoulé reçu du serveur - Synchronisation avec le serveur');

    if (!this.hasAnswered) {
      this.hasAnswered = true;
      this.waitingForPlayers = false;

      this.lastAnswerCorrect = false;
      this.overlayCorrect = false;

      this.tempPlayerScore = {
        username: this.currentUser?.username || '',
        points: 0,
        isCorrect: false
      };

      const currentPlayer = this.livePlayers.find(p => p.username === this.currentUser?.username);
      if (currentPlayer) {
        currentPlayer.lastAnswerCorrect = false;
        this.updateLiveScoreboard();
      }

      this.saveAllScoresToLocalStorage();
    }

    this.showFeedbackPhase(leaderboard);
  }

  private stopTimer(): void {
    if (this.timer) {
      clearInterval(this.timer);
      this.timer = null;
    }
  }

  private forceFeedbackDueToTimeout(): void {
    if (this.currentGame?.players) {
      this.currentGame.players.forEach(player => {
        if (!this.playersAnswered.includes(player.username)) {
          this.onPlayerAnswered(player.username);
        }
      });
    }
  }

  private onPlayerAnswered(username: string): void {
    if (!this.playersAnswered.includes(username)) {
      this.playersAnswered.push(username);

      if (this.hasAnswered) {
        this.waitingForPlayers = true;
      }

      const player = this.livePlayers.find(p => p.username === username);
      if (player) {
        player.isOnline = true;
      }
      setTimeout(() => {
        this.saveAllScoresToLocalStorage();

      }, 1000);

      if (this.playersAnswered.length >= this.totalPlayers) {
        const realLeaderboard = this.livePlayers.map(player => {

          return {
            username: player.username,
            score: player.score,
            isCorrect: player.username === this.currentUser?.username && this.tempPlayerScore
              ? this.tempPlayerScore.isCorrect
              : (player.lastAnswerCorrect || false)
          };
        });

        this.showFeedbackPhase(realLeaderboard);
      }
    }
  }

  private showFeedbackPhase(leaderboard: any[]): void {
    this.stopTimer();
    this.showFeedback = true;
    this.waitingForPlayers = false;
    this.finalLeaderboard = leaderboard;

    this.updateFinalScores(leaderboard);

    this.timeLeft = 3;

    setTimeout(() => {
      this.showFeedback = false;
      this.showTransitionScreen(leaderboard);
      this.updateLiveScoreboardAfterFeedback();

    }, 3000);
  }

  private updateFinalScores(leaderboard: any[]): void {

    leaderboard.forEach(entry => {
      const player = this.livePlayers.find(p => p.username === entry.username);
      if (player) {
        player.lastAnswerCorrect = entry.isCorrect;
        if (entry.score !== undefined) {
          const oldScore = player.score;
          if (entry.score !== oldScore) {

            player.score = entry.score;
            if (player.username === this.getCurrentUsername()) {
              this.currentScore = entry.score;

            }
          } else {
          }
        }
      }
    });

    this.updateLiveScoreboard();

  }

  private updateLiveScoreboardAfterFeedback(): void {
    if (this.tempPlayerScore) {
      this.updatePlayerScore(this.tempPlayerScore.username, this.tempPlayerScore.isCorrect, this.tempPlayerScore.points);
      this.tempPlayerScore = null;
    }
    this.forceScoreboardUpdate();
    this.syncAllPlayerScores();
    this.updateLiveScoreboard();
  }

  private forceScoreboardUpdate(): void {
    if (this.currentGame?.id) {
      this.livePlayers.forEach(player => {
        if (player.username === this.currentUser?.username) {
          if (this.currentScore > player.score) {
            const oldScore = player.score;
            player.score = this.currentScore;

          } else {

          }
        } else {
        }
      });

      this.syncAllPlayerScores();
      this.updateLiveScoreboard();

    }
  }

  private syncAllPlayerScores(): void {
    if (this.currentGame?.id) {

      this.multiplayerService.getGameStatus(this.currentGame.id).subscribe({
        next: (game) => {
        let scoresUpdated = false;

        if (game.playerScores) {
          this.livePlayers.forEach(player => {
            const serverPlayer = game.players.find(p => p.username === player.username);
            if (serverPlayer && game.playerScores[serverPlayer.id] !== undefined) {
              const serverScore = game.playerScores[serverPlayer.id];
              const oldScore = player.score;
              if (serverScore > oldScore) {
                player.score = serverScore;
                scoresUpdated = true;
              } else if (serverScore === 0 && oldScore === 0) {
              } else {
              }
            }
          });
        }

        if (game.sharedScores && Object.keys(game.sharedScores).length > 0) {

          this.livePlayers.forEach(player => {
            if (game.sharedScores && game.sharedScores[player.username] !== undefined) {
              const serverScore = game.sharedScores[player.username];
              const oldScore = player.score;

              if (serverScore !== oldScore && serverScore >= 0) {
                player.score = serverScore;
                scoresUpdated = true;

                if (player.username === this.getCurrentUsername()) {
                  this.currentScore = serverScore;
                }
              } else {
              }
            }
          });

          if (scoresUpdated) {
            this.updateLiveScoreboard();
            this.saveAllScoresToLocalStorage();
            return;
          }
        }

        if (game.leaderboard && game.leaderboard.length > 0) {

          this.livePlayers.forEach(player => {
            const leaderboardEntry = game.leaderboard.find((entry: any) => entry.username === player.username);
            if (leaderboardEntry && leaderboardEntry.score !== undefined) {
              const serverScore = leaderboardEntry.score;
              const oldScore = player.score;

              if (serverScore > oldScore) {
                player.score = serverScore;
                scoresUpdated = true;
              } else if (serverScore === 0 && oldScore === 0) {
              } else {
              }
            }
          });
        }

        if (game.players && game.players.length > 0) {

          this.livePlayers.forEach(player => {
            const serverPlayer = game.players.find((p: any) => p.username === player.username);
            if (serverPlayer && (serverPlayer as any).score !== undefined) {
              const serverScore = (serverPlayer as any).score;
              const oldScore = player.score;

              if (serverScore > oldScore) {
                player.score = serverScore;
                scoresUpdated = true;
              } else if (serverScore === 0 && oldScore === 0) {
              } else {
              }
            }
          });
        }

        if (scoresUpdated) {
          this.updateLiveScoreboard();

          this.saveAllScoresToLocalStorage();
        } else {
        }
        },
        error: (error) => {
          console.error('Erreur synchronisation scores:', error);
        }
      });
    }
  }

  private proceedToNextQuestion(): void {
    // Demander au backend de passer à la question suivante
    if (this.currentGame?.id) {
      this.multiplayerService.triggerNextQuestion(this.currentGame.id).subscribe({
        next: (result: any) => {
          if (result.success) {
            this.proceedToNextQuestionLocal();
          }
        },
        error: (error: any) => {
          console.error('Erreur lors du déclenchement de la question suivante:', error);
          // Fallback : utiliser la logique locale
          this.proceedToNextQuestionLocal();
        }
      });
    } else {
      this.proceedToNextQuestionLocal();
    }
  }

  private proceedToNextQuestionLocal(): void {

    const nextQuestionIndex = this.currentQuestionIndex - 1;

    if (nextQuestionIndex >= 0 && nextQuestionIndex < this.totalQuestions && this.questions && this.questions[nextQuestionIndex]) {
      setTimeout(() => {
        this.startNewQuestion(nextQuestionIndex, this.questions[nextQuestionIndex], 30);
      }, 500);
    } else {
      this.endGameOnServer();

      const currentUser = this.getCurrentUsername();
      const currentUserInGame = this.currentUser?.username;

      const finalLeaderboard = this.currentGame?.players.map((player) => {
        let realScore;
        if (player.username === currentUser || player.username === currentUserInGame) {
          realScore = this.currentScore;
        } else {
          const livePlayer = this.livePlayers.find(p => p.username === player.username);
          realScore = livePlayer?.score || 0;
        }

        return {
          username: player.username,
          score: realScore,
          rank: 1,
          totalQuestions: this.totalQuestions,
          correctAnswers: Math.floor(realScore / 10),
          timeBonus: 0
        };
      }) || [];

      finalLeaderboard.sort((a, b) => b.score - a.score);
      finalLeaderboard.forEach((player, index) => {
        player.rank = index + 1;
      });

      this.showFinalResults(finalLeaderboard);
    }
  }

  private showFinalResults(leaderboard: any[]): void {
    this.stopTimer();
    this.quizCompleted = true;
    this.gameCompleted = true;
    this.showFeedback = false;
    this.waitingForPlayers = false;
    this.finalLeaderboard = leaderboard;

    const currentUser = this.getCurrentUsername();

    let currentPlayerStats = leaderboard.find(p => p.username === currentUser);

    if (!currentPlayerStats && this.currentUser) {
      currentPlayerStats = leaderboard.find(p => p.username === this.currentUser.username);
    }

    if (currentPlayerStats) {
      this.playerRank = currentPlayerStats.rank;
      this.currentScore = currentPlayerStats.score;
    } else {
      this.playerRank = this.totalPlayers;
    }

    this.timeLeft = 0;
  }

  selectAnswer(answerId: number): void {
    if (this.hasAnswered || !this.currentQuestion) return;

    if (this.currentQuestion.type_question?.name === 'multiple_choice') {
      const index = this.selectedAnswers.indexOf(answerId);
      if (index > -1) {
        this.selectedAnswers.splice(index, 1);
      } else {
        this.selectedAnswers.push(answerId);
      }
    }
  }

  selectSingleAnswer(answerId: number): void {
    if (this.hasAnswered || answerId === 0) return;
    this.selectedAnswer = answerId;
  }

  canValidate(): boolean {
    if (!this.currentQuestion) return false;

    if (this.currentQuestion.type_question?.name === 'multiple_choice') {
      return this.selectedAnswers.length > 0;
    } else {
      return this.selectedAnswer !== null;
    }
  }

  submitAnswer(): void {
    if (!this.canValidate() || this.hasAnswered || !this.currentQuestion) return;

    const timeSpent = Math.floor((Date.now() - this.questionStartTime) / 1000);
    let finalAnswer: any;

    if (this.currentQuestion.type_question?.name === 'multiple_choice') {
      finalAnswer = this.selectedAnswers;
    } else {
      finalAnswer = this.selectedAnswer;
    }

    this.stopTimer();

    this.hasAnswered = true;
    this.waitingForPlayers = false;


    this.multiplayerService.submitAnswer(
      this.gameId,
      this.currentQuestion.id,
      finalAnswer,
      timeSpent
    ).subscribe({
      next: (result) => {

        const backendPoints = result.points || 0;
        const currentPlayer = this.livePlayers.find(p => p.username === this.currentUser?.username);
        const actualCurrentScore = currentPlayer?.score || 0;
        const feedbackScore = actualCurrentScore + backendPoints;

        this.lastAnswerCorrect = result.isCorrect;
        this.overlayCorrect = result.isCorrect;

        this.tempPlayerScore = {
          username: this.currentUser?.username || '',
          points: backendPoints,
          isCorrect: result.isCorrect
        };

        if (currentPlayer) {
          const oldScore = currentPlayer.score;
          currentPlayer.score = feedbackScore;

          this.updateLiveScoreboard();
        }

        this.currentScore = feedbackScore;

        if (this.currentUser) {
          this.questionPointsGained[this.currentUser.username] = backendPoints;
          this.lastAnswerResults[this.currentUser.username] = result.isCorrect;
        }

        this.shareCurrentPlayerScoreWithServer(feedbackScore);

        this.saveAllScoresToLocalStorage();

        if (this.currentUser?.username) {
          this.onPlayerAnswered(this.currentUser.username);
        }

        setTimeout(() => {
          this.waitingForPlayers = true;
        }, 2000);
      },
      error: (error) => {
        console.error('Erreur lors de la soumission de la réponse:', error);

        // Réinitialiser l'état en cas d'erreur
        this.hasAnswered = false;
        this.waitingForPlayers = false;

        // Afficher un message d'erreur à l'utilisateur
        if (error.error && error.error.details) {
          console.error('Détails de l\'erreur:', error.error.details);
        }

        // Relancer le timer si l'erreur n'est pas critique
        if (error.status !== 400) {
          this.startTimer();
        }
      }
    });
  }

  private resetAnswers(): void {
    this.selectedAnswer = null;
    this.selectedAnswers = [];
    this.hasAnswered = false;
    this.waitingForPlayers = false;
  }

  private getCurrentUsername(): string | null {
    const token = localStorage.getItem('JWT_TOKEN');
    if (!token) return null;

    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      return payload.username || payload.sub || null;
    } catch {
      return null;
    }
  }

  private findCurrentUser(players: any[], tokenUsername: string | null): any {
    if (!tokenUsername || !players) return null;

    let found = players.find(p => p.username === tokenUsername);
    if (found) {
      return found;
    }

    found = players.find(p => p.email === tokenUsername);
    if (found) {
      return found;
    }

        const token = localStorage.getItem('JWT_TOKEN');
        if (token) {
          try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            const userId = payload.userId || payload.id;
            found = players.find(p => p.id === userId);
            if (found) {
              return found;
            }
          } catch (e) {
          }
        }

    return null;
  }

  getUserInitials(): string {
    const username = this.currentUser?.username || 'U';
    return username.charAt(0).toUpperCase();
  }

  getProgressPercentage(): number {
    if (this.totalQuestions === 0) return 0;
    return ((this.currentQuestionIndex + 1) / this.totalQuestions) * 100;
  }

  private normalizeScoreToPercentage(score: number, totalQuestions: number): number {
    if (totalQuestions === 0) return 0;
    return Math.min(Math.round((score / (totalQuestions * 10)) * 100), 100);
  }

  getCurrentNormalizedScore(): number {
    return this.normalizeScoreToPercentage(this.currentScore, this.totalQuestions);
  }

  onAnswerSelected(answer: any): void {
    this.selectedAnswer = answer?.id || answer;
  }

  onAnswersSelected(answers: any[]): void {
    this.selectedAnswers = answers.map(a => a?.id || a);
  }

  onOrderChanged(order: any[]): void {

  }

  onMatchingAnswersSelected(matches: any): void {
  }

  validateAnswer(): void {
    this.submitAnswer();
  }

  shareQuiz(): void {
    if (!this.currentGame) return;

    const gameUrl = `${window.location.origin}/multiplayer/game/${this.gameId}`;
    if (navigator.share) {
      navigator.share({
        title: this.currentGame.quiz.title,
        text: `Rejoignez-moi dans ce quiz multijoueur !`,
        url: gameUrl
      });
    } else {
      navigator.clipboard.writeText(gameUrl);
    }
  }


  rateQuiz(rating: number): void {
    this.userRating = rating;
  }

  trackByStar(index: number, star: number): number {
    return star;
  }



  getProgressObject() {
    return {
      current: this.currentQuestionIndex,
      total: this.totalQuestions,
      percentage: this.getProgressPercentage()
    };
  }


  replayQuiz(): void {
    if (this.currentGame?.roomId) {
      this.router.navigate(['/multiplayer/room', this.currentGame.roomId]);
    } else {
      this.router.navigate(['/quiz']);
    }
  }

  endGameOnServer(): void {
    if (!this.gameId) return;

    this.multiplayerService.endGame(this.gameId).subscribe({
      next: (result) => {
      },
      error: (error) => {
      }
    });
  }

  private preventNavigation(): void {
    window.addEventListener('beforeunload', (event) => {
      if (this.currentGame && !this.gameCompleted) {
        event.preventDefault();
        event.returnValue = 'Êtes-vous sûr de vouloir quitter la partie ? Votre progression sera perdue.';
        return event.returnValue;
      }
    });
  }

  private setupLocalStorageSync(): void {
    window.addEventListener('storage', (event) => {
      if (event.key && event.key.startsWith('game_scores_') && event.newValue) {
      }
    });

    if (this.currentGame?.id && this.livePlayers.length > 0) {
      if (this.syncInterval) {
        clearInterval(this.syncInterval);
      }

      this.syncInterval = setInterval(() => {
        if (this.currentGame?.id && this.livePlayers.length > 0 && !this.isSyncing) {
          this.syncAllPlayerScores();
        }
      }, 8000);
    }
  }


  private saveAllScoresToLocalStorage(): void {
    const gameId = this.currentGame?.id;
    if (!gameId || !this.livePlayers.length) return;

    const storageKey = `game_scores_${gameId}`;
    const allScores: { [username: string]: number } = {};

    this.livePlayers.forEach(player => {
      allScores[player.username] = player.score;
    });

    const nonZeroScores = Object.entries(allScores).filter(([username, score]) => score > 0);
    const zeroScores = Object.entries(allScores).filter(([username, score]) => score === 0);



    if (nonZeroScores.length === 0) {
      console.warn(' ATTENTION: Tous les scores sont à 0, possible problème de synchronisation');
      return;
    }

    localStorage.setItem(storageKey, JSON.stringify(allScores));

    if (zeroScores.length > 0) {
      console.warn(' ATTENTION: Scores à 0 détectés dans localStorage:', zeroScores);
    }
  }

  private shareCurrentPlayerScoreWithServer(score: number): void {
    if (this.currentGame?.id && this.currentUser?.username) {
      const currentPlayer = this.livePlayers.find(p => p.username === this.currentUser?.username);
      const existingScore = currentPlayer?.score || 0;

      if (score < existingScore) {
        console.warn(`Score ignoré pour ${this.currentUser.username}: ${score} < ${existingScore} (existant)`);
        return;
      }


      const playerScores: { [username: string]: number } = {
        [this.currentUser.username]: score
      };

      this.multiplayerService.submitPlayerScores(this.currentGame.id, playerScores).subscribe({
        next: (result: any) => {
        },
        error: (error: any) => {
          console.error(` Erreur partage score pour ${this.currentUser.username}:`, error);
        }
      });
    }
  }


  private showTransitionScreen(leaderboard: any[]): void {

    const gameId = this.currentGame?.id;
    const storageKey = `game_scores_${gameId}`;
    const realScores = JSON.parse(localStorage.getItem(storageKey) || '{}');

    this.multiplayerService.getGameStatus(this.gameId).subscribe({
      next: (game: any) => {
        if (game.sharedScores && Object.keys(game.sharedScores).length > 0) {
          this.buildTransitionWithScores(game.sharedScores);
        } else {

          this.buildTransitionWithScores(realScores);
        }
      },
      error: (error) => {
        console.error(' Erreur récupération scores serveur, fallback vers localStorage:', error);
        this.buildTransitionWithScores(realScores);
      }
    });
  }

  private buildTransitionWithScores(scores: any): void {

    const playersWithRealScores = (this.currentGame?.players || []).map((player: any) => {
      const realScore = scores[player.username] || 0;
      return {
        username: player.username,
        score: realScore
      };
    }).sort((a, b) => b.score - a.score);

    this.transitionPlayers = playersWithRealScores.map((player) => {
        const pointsGained = this.questionPointsGained[player.username] || 0;
        const lastAnswerCorrect = this.lastAnswerResults[player.username];

        const gamePlayer: any = this.currentGame?.players?.find((p: any) => p.username === player.username);

        const calculatedPercentage = this.normalizeScoreToPercentage(player.score, this.totalQuestions);

        return {
          id: gamePlayer?.id || 0,
          username: player.username,
          email: gamePlayer?.email || '',
          avatar: gamePlayer?.avatar ? {
            shape: gamePlayer.avatar.shape || 'blob_circle',
            color: gamePlayer.avatar.color || '#91DEDA'
          } : {
            shape: 'blob_circle',
            color: '#91DEDA'
          },
          score: player.score,
          rank: 0,
          isCurrentUser: player.username === this.currentUser?.username,
          lastAnswerCorrect: lastAnswerCorrect !== undefined ? lastAnswerCorrect : true,
          scorePercentage: calculatedPercentage,
          pointsGained: pointsGained
        };
      });

    this.showTransition = true;

    setTimeout(() => {
      this.showTransition = false;
      this.questionPointsGained = {};
    }, this.transitionDuration);
  }
}
