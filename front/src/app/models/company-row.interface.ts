export interface CompanyRow {
  id: number;
  selected: boolean;
  name: string;
  userCount: number;
  activeUsers: number;
  collaboratorCount: number;
  groupName: string | null;
  groups: Group[];
  users?: CompanyUser[];
  createdAt: string;
  lastActivity: string;
}

export interface Group {
  id: number;
  name: string;
}

export interface CompanyUser {
  id: number;
  userAnswers?: UserAnswer[];
}

export interface UserAnswer {
  id: number;
  isCorrect: boolean;
}
