import { Component, OnInit, OnDestroy } from '@angular/core';
import {CommonModule, NgOptimizedImage} from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { QuizGameService } from '../../services/quiz-game.service';
import { QuizResultsService } from '../../services/quiz-results.service';
import { interval, Subscription } from 'rxjs';
import { trigger, style, animate, transition } from '@angular/animations';

import { McqQuestionComponent } from '../../components/question-types/mcq-question/mcq-question.component';
import { MultipleChoiceQuestionComponent } from '../../components/question-types/multiple-choice-question/multiple-choice-question.component';
import { RightOrderQuestionComponent } from '../../components/question-types/right-order-question/right-order-question.component';
import { MatchingQuestionComponent } from '../../components/question-types/matching-question/matching-question.component';
import { IntruderQuestionComponent } from '../../components/question-types/intruder-question/intruder-question.component';
import { BlindTestQuestionComponent } from '../../components/question-types/blind-test-question/blind-test-question.component';
import { TrueFalseQuestionComponent } from '../../components/question-types/true-false-question/true-false-question.component';


import { Question, Answer } from '../../models/quiz.model';
import { SlideButtonComponent } from '../../components/slide-button/slide-button.component';
import { BackButtonComponent } from '../../components/back-button/back-button.component';
import { TuiIcon } from '@taiga-ui/core';
import { AlertService } from '../../services/alert.service';
import { AnalyticsService } from '../../services/analytics.service';


@Component({
    selector: 'app-quiz-game',
    standalone: true,
  imports: [
    CommonModule,
    McqQuestionComponent,
    MultipleChoiceQuestionComponent,
    RightOrderQuestionComponent,
    MatchingQuestionComponent,
    IntruderQuestionComponent,
    BlindTestQuestionComponent,
    TrueFalseQuestionComponent,
    SlideButtonComponent,
    BackButtonComponent,
    TuiIcon,
    NgOptimizedImage
  ],
    templateUrl: './quiz-game.component.html',
    styleUrls: ['./quiz-game.component.scss', './quiz-game-end.scss'],
    animations: [
        trigger('slideFeedback', [
            transition(':enter', [
                style({ transform: 'translateX(100%)', opacity: 0 }),
                animate(
                    '600ms cubic-bezier(0.25, 0.8, 0.25, 1)',
                    style({ transform: 'translateX(0)', opacity: 1 })
                )
            ]),
            transition(':leave', [
                animate(
                    '300ms ease-in',
                    style({ opacity: 0, transform: 'scale(0.95)' })
                )
            ])
        ])
    ]
})
export class QuizGameComponent implements OnInit, OnDestroy {
    currentQuestion: Question | null = null;
    currentQuestionIndex = 1;
    totalQuestions = 0;
    selectedAnswer: Answer | null = null;
    selectedAnswers: Answer[] = [];
    quizData: any = null;

    showFeedback = false;
    overlayCorrect = false;
    showCorrectAnswer = false;
    correctAnswers: Answer[] = [];

    currentScore = 0;
    totalScore = 0;
    timeLeft = 300000000;
    private timerSub?: Subscription;

    isLoading = false;
    quizCompleted = false;

    playerRank = 1;
    totalPlayers = 100;
    userRating = 0;
    hoverRating = 0;
    leaderboard: any[] = [];

    private feedbackTimeout?: ReturnType<typeof setTimeout>;
    isLoadingLeaderboard = false;

    constructor(
        private route: ActivatedRoute,
        private router: Router,
        private quizGameService: QuizGameService,
        private quizResultsService: QuizResultsService,
        private alertService: AlertService,
        private analytics: AnalyticsService
    ) { }

    ngOnInit(): void {
        const quizId = +this.route.snapshot.params['id'];
        this.analytics.trackQuizView();

        this.loadQuiz(quizId);
    }

    ngOnDestroy(): void {
        this.timerSub?.unsubscribe();
        clearTimeout(this.feedbackTimeout);
    }

    private loadQuiz(id: number): void {
        this.isLoading = true;
        this.quizGameService.loadQuiz(id).subscribe({
            next: quiz => {
                this.quizData = quiz;
                this.totalQuestions = quiz.questions.length;
                this.currentQuestion = this.formatQuestion(quiz.questions[0]);
                this.startTimer();
                this.isLoading = false;
            },
            error: err => {
                this.isLoading = false;
            }
        });
    }

    private formatQuestion(q: any): Question {
        return {
            id: q.id,
            question: q.question,
            type_question: q.type_question?.name || q.type,
            answers: q.answers.map((a: any) => ({
                id: a.id,
                answer: a.answer,
                is_correct: a.is_correct,
                pair_id: a.pair_id,
                order_correct: a.order_correct
            }))
        };
    }

    private startTimer(): void {
        this.timeLeft = 3000000;
        this.timerSub?.unsubscribe();
        this.timerSub = interval(1000).subscribe(() => {
            if (--this.timeLeft <= 0) this.timeUp();
        });
    }

    private stopTimer(): void {
        this.timerSub?.unsubscribe();
    }

    private timeUp(): void {
        this.stopTimer();
        this.overlayCorrect = false;
        this.showCorrectAnswer = true;
        this.highlightCorrectAnswers();
        this.launchFeedbackPanel();
    }

    onAnswerSelected(id: number) {
        this.selectedAnswer = this.currentQuestion?.answers.find(a => a.id === id) || null;
    }

    onAnswersSelected(ids: number[]) {
        if (!this.currentQuestion) return;
        this.selectedAnswers = this.currentQuestion.answers.filter(a => ids.includes(a.id));
    }

    onOrderChanged(ids: number[]) {
        if (!this.currentQuestion) return;
        this.selectedAnswers = ids.map(id => this.currentQuestion!.answers.find(a => a.id === id)!).filter(Boolean);
    }

    onMatchingAnswersSelected(map: { [l: string]: string }) {
        if (!this.currentQuestion) return;
        this.selectedAnswers = Object.entries(map)
            .map(([l, r]) => {
                const left = this.currentQuestion!.answers.find(a => a.id.toString() === l);
                const right = this.currentQuestion!.answers.find(a => a.id.toString() === r);
                return left && right ? { left, right } : null;
            })
            .filter(Boolean) as any;
    }

    validateAnswer(): void {
        if (!this.currentQuestion) return;
        this.stopTimer();

        const t = this.currentQuestion.type_question;
        let ok = false;

        switch (t) {
            case 'MCQ':
            case 'QCM':
            case 'true_false':
            case 'find_the_intruder':
            case 'blind_test':
                ok = !!this.selectedAnswer?.is_correct;
                break;

            case 'multiple_choice':
                const correct = this.currentQuestion.answers.filter(a => a.is_correct);
                ok = correct.length === this.selectedAnswers.length &&
                    correct.every(c => this.selectedAnswers.some(s => s.id === c.id));
                break;

            case 'right_order':
                const correctOrder = [...this.currentQuestion.answers]
                    .sort((a, b) => (a.order_correct ?? 0) - (b.order_correct ?? 0))
                    .map(a => a.id);
                const chosenOrder = this.selectedAnswers.map(a => a.id);
                ok = correctOrder.length === chosenOrder.length &&
                    correctOrder.every((id, i) => id === chosenOrder[i]);
                break;

            case 'matching':
                ok = (this.selectedAnswers as any[]).every(p => {
                    const l = p.left?.pair_id?.replace('left_', '');
                    const r = p.right?.pair_id?.replace('right_', '');
                    return l === r;
                });
                break;
        }

        if (ok) this.currentScore += 10;
        else {
            this.showCorrectAnswer = true;
            this.highlightCorrectAnswers();
        }

        this.overlayCorrect = ok;
        this.launchFeedbackPanel();
    }

    private launchFeedbackPanel(): void {
        this.showFeedback = true;

        clearTimeout(this.feedbackTimeout);
        this.feedbackTimeout = setTimeout(() => {
            this.showFeedback = false;
            setTimeout(() => this.nextQuestion(), 450);
        }, 5000);
    }

    private nextQuestion(): void {
        this.showCorrectAnswer = false;
        this.correctAnswers = [];
        this.selectedAnswer = null;
        this.selectedAnswers = [];


        if (this.currentQuestionIndex < this.totalQuestions) {
            this.currentQuestionIndex++;
            this.currentQuestion = this.formatQuestion(
                this.quizData.questions[this.currentQuestionIndex - 1]);
            this.startTimer();
        } else {
            this.finishQuiz();
        }
    }

    private highlightCorrectAnswers() {
        if (this.currentQuestion)
            this.correctAnswers = this.currentQuestion.answers.filter(a => a.is_correct);
    }

    private finishQuiz(): void {
        const correctAnswers = Math.floor(this.currentScore / 10);
        this.totalScore = this.totalQuestions > 0 ? Math.round((correctAnswers / this.totalQuestions) * 100) : 0;
        this.analytics.trackQuizComplete(this.quizData.id.toString(), this.totalScore);

        this.quizCompleted = true;
        this.loadPlayerRanking();
    }



    getProgressPercentage(): number {
        return (this.currentQuestionIndex / this.totalQuestions) * 100;
    }

    canValidate(): boolean {
        if (!this.currentQuestion) return false;
        const t = this.currentQuestion.type_question;
        if (['MCQ', 'QCM', 'Vrai/Faux', 'find_the_intruder', 'blind_test'].includes(t))
            return !!this.selectedAnswer;
        if (t === 'multiple_choice') return this.selectedAnswers.length > 0;
        if (t === 'right_order') return this.selectedAnswers.length === this.currentQuestion.answers.length;
        if (t === 'matching') {
            const left = this.currentQuestion.answers.filter(a => a.pair_id?.startsWith('left_'));
            return this.selectedAnswers.length === left.length;
        }
        return false;
    }

    getScorePercentage(): number {
        return this.totalScore;
    }

    getCurrentNormalizedScore(): number {
        const correctAnswers = Math.floor(this.currentScore / 10);
        return this.totalQuestions > 0 ? Math.round((correctAnswers / this.totalQuestions) * 100) : 0;
    }

    private loadPlayerRanking(): void {
        if (!this.quizData?.id) return;

        this.isLoadingLeaderboard = true;

        const result = {
            quizId: this.quizData.id,
            score: this.totalScore
        };

        this.quizResultsService.saveQuizResult(result).subscribe({
            next: () => {
                this.loadRealLeaderboard();
            },
            error: (error) => {
                this.loadRealLeaderboard();
            }
        });
    }

    private loadRealLeaderboard(): void {
        this.quizResultsService.getQuizLeaderboard(this.quizData.id).subscribe({
            next: (data) => {
                this.leaderboard = data.leaderboard || [];
                this.playerRank = data.currentUserRank || 1;
                this.totalPlayers = data.totalPlayers || 1;
                this.isLoadingLeaderboard = false;
            },
            error: (error) => {
                this.isLoadingLeaderboard = false;
            }
        });
    }


    rateQuiz(rating: number): void {
        this.userRating = rating;

        const ratingData = {
            quizId: this.quizData.id,
            rating: rating
        };

        this.quizResultsService.rateQuiz(ratingData).subscribe({
            next: () => {
                this.notifyRatingChanged(rating);
            },
            error: (error) => {
            }
        });
    }

    private notifyRatingChanged(rating: number): void {
        const ratingUpdate = {
            quizId: this.quizData.id,
            rating: rating,
            timestamp: Date.now()
        };
        sessionStorage.setItem('quiz-rating-update', JSON.stringify(ratingUpdate));

        window.dispatchEvent(new CustomEvent('quiz-rating-updated', {
            detail: ratingUpdate
        }));
    }

    shareQuiz(): void {
        const quizUrl = `${window.location.origin}/quiz/${this.quizData.id}/play`;
        if (navigator.share) {
            navigator.share({
                title: this.quizData.title,
                text: `J'ai fait ${this.getScorePercentage()}% à ce quiz !`,
                url: quizUrl
            });
        } else {
            this.copyQuizLink();
        }
    }

    replayQuiz(): void {
        this.quizCompleted = false;
        this.currentQuestionIndex = 1;
        this.currentScore = 0;
        this.totalScore = 0;
        this.selectedAnswer = null;
        this.selectedAnswers = [];
        this.showFeedback = false;
        this.loadQuiz(this.quizData.id);
    }

    goBackToQuizzes(): void {
        this.router.navigate(['/quiz']);
    }

    showFullLeaderboard(): void {
        const resultData = {
            quizTitle: this.quizData?.title || 'Quiz',
            totalScore: this.totalScore,
            totalQuestions: this.totalQuestions,
            playerRank: this.playerRank,
            totalPlayers: this.totalPlayers,
            leaderboard: this.leaderboard,
            quizId: this.quizData?.id
        };

        sessionStorage.setItem('quiz-results', JSON.stringify(resultData));

        this.router.navigate(['/quiz', this.quizData.id, 'results'], {
            state: resultData
        });
    }

    copyQuizLink(): void {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            this.alertService.success('Lien copié dans le presse-papier !');
        }).catch(() => {
            this.alertService.error('Erreur lors de la copie du lien');
        });
    }
}
