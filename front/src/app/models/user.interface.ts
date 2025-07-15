export interface User {
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
  badges?: Badge[];
  quizs?: Quiz[];
  groups?: Group[];
}

export interface Badge {
  id: number;
  name: string;
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
