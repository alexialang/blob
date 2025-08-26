import { Injectable } from '@angular/core';

export type GamePhase = 'loading' | 'question' | 'feedback' | 'transition' | 'finished';

export interface GameState {
  phase: GamePhase;
  currentQuestionIndex: number;
  totalQuestions: number;
  hasAnswered: boolean;
  waitingForPlayers: boolean;
  showFeedback: boolean;
  gameCompleted: boolean;
  isTransitioning: boolean;
  feedbackActive: boolean;
}


@Injectable({
  providedIn: 'root'
})
export class MultiplayerStateService {

  /**
   * Initialise l'état du jeu
   */
  initializeGameState(totalQuestions: number = 0): GameState {
    return {
      phase: 'loading',
      currentQuestionIndex: 0,
      totalQuestions,
      hasAnswered: false,
      waitingForPlayers: false,
      showFeedback: false,
      gameCompleted: false,
      isTransitioning: false,
      feedbackActive: false
    };
  }

  /**
   * Met à jour l'état pour une nouvelle question
   */
  setQuestionPhase(state: GameState, questionIndex: number): GameState {
    return {
      ...state,
      phase: 'question',
      currentQuestionIndex: questionIndex,
      hasAnswered: false,
      waitingForPlayers: false,
      showFeedback: false,
      isTransitioning: false,
      feedbackActive: false
    };
  }

  /**
   * Met à jour l'état pour la phase de feedback
   */
  setFeedbackPhase(state: GameState): GameState {
    if (state.feedbackActive) {
      console.log('Feedback déjà actif, ignoré');
      return state;
    }

    console.log('📊 Phase de feedback activée');
    return {
      ...state,
      phase: 'feedback',
      showFeedback: true,
      waitingForPlayers: false,
      feedbackActive: true
    };
  }

  /**
   * Met à jour l'état pour la phase de transition
   */
  setTransitionPhase(state: GameState): GameState {
    return {
      ...state,
      phase: 'transition',
      showFeedback: false,
      feedbackActive: false,
      isTransitioning: true
    };
  }

  /**
   * Met à jour l'état pour la fin du jeu
   */
  setFinishedPhase(state: GameState): GameState {
    return {
      ...state,
      phase: 'finished',
      gameCompleted: true,
      showFeedback: true,
      waitingForPlayers: false,
      isTransitioning: false,
      feedbackActive: false
    };
  }

  /**
   * Marque qu'une réponse a été soumise
   */
  markAnswerSubmitted(state: GameState): GameState {
    return {
      ...state,
      hasAnswered: true,
      waitingForPlayers: true
    };
  }

  /**
   * Vérifie si on peut passer à la question suivante
   */
  canProceedToNextQuestion(state: GameState): boolean {
    return !state.isTransitioning &&
           !state.feedbackActive &&
           state.currentQuestionIndex < state.totalQuestions - 1;
  }

  /**
   * Vérifie si le jeu est terminé
   */
  isGameFinished(state: GameState): boolean {
    return state.currentQuestionIndex >= state.totalQuestions - 1 || state.gameCompleted;
  }

  /**
   * Calcule le pourcentage de progression
   */
  getProgressPercentage(state: GameState): number {
    if (state.totalQuestions === 0) return 0;
    return ((state.currentQuestionIndex + 1) / state.totalQuestions) * 100;
  }

  /**
   * Obtient l'index d'affichage de la question courante (1-based)
   */
  getCurrentQuestionDisplayIndex(state: GameState): number {
    return state.currentQuestionIndex + 1;
  }

  /**
   * Vérifie si une transition peut être effectuée
   */
  canTransition(state: GameState, lastProcessedQuestionIndex: number): boolean {
    if (state.isTransitioning) {
      console.log('Transition déjà en cours, ignorée');
      return false;
    }

    if (state.feedbackActive) {
      console.log('Feedback en cours, transition bloquée');
      return false;
    }

    if (state.currentQuestionIndex === lastProcessedQuestionIndex) {
      console.log('Question déjà traitée, transition ignorée');
      return false;
    }

    return true;
  }

  /**
   * Réinitialise les protections entre les questions
   */
  resetProtections(state: GameState): GameState {
    return {
      ...state,
      isTransitioning: false,
      feedbackActive: false
    };
  }

  /**
   * Debug - affiche l'état actuel
   */
  logState(state: GameState, context: string = ''): void {
    console.log(`État du jeu ${context}:`, {
      phase: state.phase,
      questionIndex: state.currentQuestionIndex,
      hasAnswered: state.hasAnswered,
      waitingForPlayers: state.waitingForPlayers,
      showFeedback: state.showFeedback,
      isTransitioning: state.isTransitioning,
      feedbackActive: state.feedbackActive,
      gameCompleted: state.gameCompleted
    });
  }
}
