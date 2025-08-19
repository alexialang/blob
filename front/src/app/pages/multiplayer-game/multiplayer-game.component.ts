import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { MultiplayerService, MultiplayerGame } from '../../services/multiplayer.service';
import { MercureService } from '../../services/mercure.service';
import { QuizGameService } from '../../services/quiz-game.service';
import { Subscription } from 'rxjs';
import { trigger, state, style, transition, animate } from '@angular/animations';
import { McqQuestionComponent } from '../../components/question-types/mcq-question/mcq-question.component';
import { MultipleChoiceQuestionComponent } from '../../components/question-types/multiple-choice-question/multiple-choice-question.component';
import { RightOrderQuestionComponent } from '../../components/question-types/right-order-question/right-order-question.component';
import { TrueFalseQuestionComponent } from '../../components/question-types/true-false-question/true-false-question.component';
import { MatchingQuestionComponent } from '../../components/question-types/matching-question/matching-question.component';
import { IntruderQuestionComponent } from '../../components/question-types/intruder-question/intruder-question.component';
import { BlindTestQuestionComponent } from '../../components/question-types/blind-test-question/blind-test-question.component';
import { BackButtonComponent } from '../../components/back-button/back-button.component';
import { SlideButtonComponent } from '../../components/slide-button/slide-button.component';
import { TuiIcon } from '@taiga-ui/core';

interface GameQuestion {
  id: number;
  question: string;
  type: string;
  type_question: string;
  answers: GameAnswer[];
}

interface GameAnswer {
  id: number;
  answer: string;
  is_correct: boolean;
}

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
  totalScore = 0;
  playerRank = 1;
  totalPlayers = 2;
  userRating = 0;
  hoverRating = 0;

  currentScore = 0;
  timeLeft = 30;
  private timer: any;

  selectedAnswer: number | null = null;
  selectedAnswers: number[] = [];

  currentUser: any = null;
  otherPlayers: any[] = [];
  playersAnswered: string[] = [];
  finalLeaderboard: any[] = [];

  hasAnswered = false;
  questionStartTime = 0;
  gameCompleted = false;
  lastAnswerCorrect = false;
  waitingForPlayers = false;
  lastQuestion: any = null;
  showFullLeaderboardScreen = false;

  private subscriptions: Subscription[] = [];
  private gameId: string = '';

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private multiplayerService: MultiplayerService,
    private mercureService: MercureService,
    private quizGameService: QuizGameService
  ) {}

  ngOnInit(): void {
    this.gameId = this.route.snapshot.params['id'];
    this.mercureService.connectWithGame(this.gameId);
    this.setupGameSubscriptions();
    this.loadGameState();
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
    this.mercureService.disconnectFromGame();
    this.stopTimer();
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
        break;
    }
  }

  private loadGameState(): void {
    this.multiplayerService.getGameStatus(this.gameId).subscribe({
      next: (game) => {
        this.currentGame = game;
        this.totalQuestions = game.quiz.questionCount;
        this.totalPlayers = game.players.length;

        const currentUsername = this.getCurrentUsername();
        this.currentUser = this.findCurrentUser(game.players, currentUsername);

        this.quizGameService.loadQuiz(game.quiz.id).subscribe({
          next: (quiz) => {
            this.questions = quiz.questions;

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



  private loadQuestionAtIndex(index: number): void {
    if (!this.questions || !this.questions[index]) return;

    const question = this.questions[index];

    this.currentQuestion = question;
    this.currentQuestionIndex = index + 1;
    this.showFeedback = false;
    this.questionStartTime = Date.now();
    this.resetAnswers();

    this.timeLeft = 30;
    this.startTimer();
  }

  private startNewQuestion(questionIndex: number, question: any, timeLeft: number): void {
    this.showFeedback = false;
    this.playersAnswered = [];
    this.questionStartTime = Date.now();
    this.resetAnswers();

    if (this.questions && this.questions[questionIndex]) {
      this.loadQuestionAtIndex(questionIndex);
    } else {
      this.currentQuestionIndex = questionIndex + 1;
      this.currentQuestion = question;

      this.timeLeft = timeLeft;
      this.startTimer();
    }
  }

  private startTimer(): void {
    this.stopTimer();
    this.timer = setInterval(() => {
      if (this.timeLeft > 0) {
        this.timeLeft--;
      } else {
        this.stopTimer();
        if (!this.hasAnswered && this.canValidate()) {
          this.submitAnswer();
        } else if (this.hasAnswered) {
          this.forceFeedbackDueToTimeout();
        }
      }
    }, 1000);
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

        if (this.playersAnswered.length >= this.totalPlayers) {
          const simulatedLeaderboard = this.playersAnswered.map(username => ({
            username: username,
            score: Math.floor(Math.random() * 100),
            isCorrect: Math.random() > 0.5
          }));

          this.showFeedbackPhase(simulatedLeaderboard);
        }
    }
  }

  private showFeedbackPhase(leaderboard: any[]): void {
    this.stopTimer();
    this.showFeedback = true;
    this.waitingForPlayers = false;
    this.finalLeaderboard = leaderboard;

    this.timeLeft = 3;

    setTimeout(() => {
      this.showFeedback = false;
      this.proceedToNextQuestion();
    }, 3000);
  }

  private proceedToNextQuestion(): void {
    const nextQuestionIndex = this.currentQuestionIndex;

    if (nextQuestionIndex < this.totalQuestions && this.questions && this.questions[nextQuestionIndex]) {
      setTimeout(() => {
        this.startNewQuestion(nextQuestionIndex, this.questions[nextQuestionIndex], 30);
      }, 500);
    } else {
      this.endGameOnServer();

      this.lastQuestion = this.currentQuestion;

      const currentUser = this.getCurrentUsername();
      const currentUserInGame = this.currentUser?.username;

      const finalLeaderboard = this.currentGame?.players.map((player) => {
        let realScore;
        if (player.username === currentUser || player.username === currentUserInGame) {
          realScore = this.currentScore;
        } else {
          realScore = Math.floor(Math.random() * this.totalQuestions) * 10;
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
      this.totalScore = currentPlayerStats.score;
    } else {
      this.playerRank = this.totalPlayers;
      this.totalScore = this.currentScore;
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

    this.multiplayerService.submitAnswer(
      this.gameId,
      this.currentQuestion.id,
      finalAnswer,
      timeSpent
    ).subscribe({
      next: (result) => {
        this.hasAnswered = true;
        this.currentScore = result.currentScore || this.currentScore;
        this.lastAnswerCorrect = result.isCorrect;
        this.overlayCorrect = result.isCorrect;

        this.waitingForPlayers = true;

        if (this.currentUser?.username) {
          this.onPlayerAnswered(this.currentUser.username);
        }


      },
      error: (error) => {
      }
    });
  }

  wasSelected(answerId: number): boolean {
    if (this.currentQuestion?.type_question?.name === 'multiple_choice') {
      return this.selectedAnswers.includes(answerId);
    } else {
      return this.selectedAnswer === answerId;
    }
  }

  backToQuiz(): void {
    this.router.navigate(['/quiz']);
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

  getCurrentNormalizedScore(): number {
    const correctAnswers = Math.floor(this.currentScore / 10);
    return this.totalQuestions > 0 ? Math.round((correctAnswers / this.totalQuestions) * 100) : 0;
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

  showFullLeaderboard(): void {
    this.showFullLeaderboardScreen = true;
  }

  closeFullLeaderboard(): void {
    this.showFullLeaderboardScreen = false;
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

  backToQuizList(): void {
    this.router.navigate(['/quiz']);
  }

  replayQuiz(): void {
    if (this.currentGame?.roomId) {
      this.router.navigate(['/multiplayer/room', this.currentGame.roomId]);
    } else {
      this.router.navigate(['/quiz']);
    }
  }

  forceAllPlayersAnswered(): void {
    if (this.currentGame?.players) {
      this.currentGame.players.forEach(player => {
        if (!this.playersAnswered.includes(player.username)) {
          this.onPlayerAnswered(player.username);
        }
      });
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
}
