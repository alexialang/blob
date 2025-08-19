export interface User {
  pseudo?: string;
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  roles: string[];
  dateRegistration: string;
  lastAccess?: string;
  isAdmin: boolean;
  isActive: boolean;
  isVerified: boolean;
  companyId?: number;
  companyName?: string;
  avatar?: string;
  avatarShape?: string;
  avatarColor?: string;
  badges?: Badge[];
  quizs?: Quiz[];
  groups?: Group[];
  userAnswers?: UserAnswer[];
  userPermissions?: UserPermission[];
}

export interface UserPermission {
  id: number;
  permission: string;
}

export interface Badge {
  id: number;
  name: string;
  description?: string;
  image?: string;
}

export interface Quiz {
  id: number;
  title: string;
  description: string;
  is_public: boolean;
  date_creation: string;
  status: string;
}

export interface Group {
  id: number;
  name: string;
}

export interface UserAnswer {
  id: number;
  answerSelected: string;
  isCorrect: boolean;
  question: {
    id: number;
    text: string;
  };
}
