export interface QuizGame {
  id: number;
  title: string;
  description: string;
  category: string;
  questions: QuizQuestion[];
}

export interface QuizQuestion {
  id: number;
  question: string;
  type: QuestionType;
  answers: QuizAnswer[];
  userAnswer?: any;
  isCorrect?: boolean;
  points?: number;
}

export interface QuizAnswer {
  id: number;
  answer: string;
  isCorrect?: boolean;
  orderCorrect?: string;
  pairId?: string;
  isIntrus?: boolean;
}

export type QuestionType = 'QCM' | 'Vrai/Faux' | 'Choix multiple' | 'Associations' | 'Ordre' | 'Intrus';

export interface GameState {
  currentQuestionIndex: number;
  totalQuestions: number;
  score: number;
  maxScore: number;
  timeSpent: number;
  isCompleted: boolean;
  answers: UserGameAnswer[];
}

export interface UserGameAnswer {
  questionId: number;
  userAnswer: any;
  isCorrect: boolean;
  points: number;
  timeSpent: number;
}

export interface GameResult {
  quiz: QuizGame;
  score: number;
  maxScore: number;
  percentage: number;
  timeSpent: number;
  correctAnswers: number;
  totalQuestions: number;
  answers: UserGameAnswer[];
  badges?: string[];
}
