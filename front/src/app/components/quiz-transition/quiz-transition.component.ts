import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Subscription } from 'rxjs';
import { QuizTransitionService } from '../../services/quiz-transition.service';
import { QuizCard } from '../../models/quiz.model';

@Component({
  selector: 'app-quiz-transition',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './quiz-transition.component.html',
  styleUrls: ['./quiz-transition.component.scss'],
})
export class QuizTransitionComponent implements OnInit, OnDestroy {
  showTransition = false;
  currentQuiz: QuizCard | null = null;
  cardPosition: any = null;
  isExiting = false;
  cardColor: string = 'var(--color-primary)';

  private subscriptions: Subscription[] = [];

  constructor(private quizTransitionService: QuizTransitionService) {}

  ngOnInit(): void {
    this.subscriptions.push(
      this.quizTransitionService.showTransition$.subscribe(show => (this.showTransition = show)),
      this.quizTransitionService.currentQuiz$.subscribe(quiz => (this.currentQuiz = quiz)),
      this.quizTransitionService.cardPosition$.subscribe(
        position => (this.cardPosition = position)
      ),
      this.quizTransitionService.isExiting$.subscribe(exiting => (this.isExiting = exiting)),
      this.quizTransitionService.cardColor$.subscribe(color => (this.cardColor = color))
    );
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  getCompanyBadgeText(): string {
    if (!this.currentQuiz?.is_public && this.currentQuiz?.groupName) {
      return this.currentQuiz.groupName;
    }
    return '';
  }
}
