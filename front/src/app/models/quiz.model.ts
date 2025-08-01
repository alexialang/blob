export interface Answer {
  id: number;
  answer: string;
  is_correct: boolean;
  order_correct?: number;
  pair_id?: string;
  is_intrus?: boolean;
}

export interface Question {
  id: number;
  question: string;
  type_question: string;
  difficulty?: 'easy' | 'medium' | 'hard';
  answers: Answer[];
}

export interface Quiz {
  id: number;
  title: string;
  description: string;
  questions: Question[];
  category?: {
    id: number;
    name: string;
  };
  status?: string;
  is_public?: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface QuizCard {
  id: number;
  title: string;
  description: string;
  is_public: boolean;
  company: string;
  groupName?: string;
  category: string;
  difficulty: 'Facile' | 'Moyen' | 'Difficile';
  rating: number;
  isLiked: boolean;
  questionCount: number;
  isFlipped: boolean;
  playMode: 'solo' | 'team';
}

export interface GameState {
  quiz: Quiz | null;
  currentQuestionIndex: number;
  answers: { [questionId: number]: any };
  score: number;
  startTime: Date;
}

export interface TypeQuestion {
  id: number;
  name: string;
  key: string;
  value?: string;
}

export interface Category {
  id: number;
  name: string;
}

export interface Group {
  id: number;
  name: string;
}

export interface Status {
  id: number;
  name: string;
  value: string;
}
