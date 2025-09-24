export interface UserRow {
  id: number;
  name: string;
  email: string;
  roles: string[];
  permissions: string[];
  quizs?: UserQuiz[];
  userAnswers?: UserAnswer[];
  badges?: UserBadge[];
}

export interface UserQuiz {
  id: number;
  title: string;
}

export interface UserAnswer {
  id: number;
  isCorrect: boolean;
}

export interface UserBadge {
  id: number;
  name: string;
}
