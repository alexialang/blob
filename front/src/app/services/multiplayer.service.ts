import { Injectable, NgZone } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, Subject } from 'rxjs';
import { environment } from '../../environments/environment';
import { MercureService } from './mercure.service';

export interface GameRoom {
  id: string;
  name: string;
  quiz: { id: number; title: string; questionCount: number };
  creator: { id: number; username: string };
  maxPlayers: number;
  isTeamMode: boolean;
  status: 'waiting' | 'playing' | 'finished';
  players: GamePlayer[];
  teams?: { team1: number[]; team2: number[] };
  createdAt: number;
  gameStartedAt?: number;
  gameId?: string;
}

export interface GamePlayer {
  id: number;
  username: string;
  isReady: boolean;
  isCreator: boolean;
  team?: string | null;
}

export interface MultiplayerGame {
  id: string;
  roomId: string;
  quiz: { id: number; title: string; questionCount: number };
  players: GamePlayer[];
  isTeamMode: boolean;
  teams?: { team1: number[]; team2: number[] };
  status: 'playing' | 'finished';
  currentQuestionIndex: number;
  startedAt: number;
  playerScores: { [playerId: number]: number };
  leaderboard: LeaderboardEntry[];
  sharedScores?: { [username: string]: number };
}

export interface LeaderboardEntry {
  userId: number;
  username: string;
  score: number;
  position: number;
  team?: string | null;
}

export interface GameInvitation {
  roomId: string;
  roomName: string;
  senderName: string;
  quiz: { id: number; title: string; questionCount: number };
  currentPlayers: number;
  maxPlayers: number;
}

@Injectable({
  providedIn: 'root'
})
export class MultiplayerService {
  private readonly apiUrl = environment.apiBaseUrl;
  public eventSource?: EventSource;

  private availableRooms$ = new BehaviorSubject<GameRoom[]>([]);
  private currentRoom$ = new BehaviorSubject<GameRoom | null>(null);
  private currentGame$ = new BehaviorSubject<MultiplayerGame | null>(null);
  private invitations$ = new Subject<GameInvitation>();
  private playerAnswered$ = new Subject<{
    userId: number;
    username: string;
    isCorrect: boolean;
    points: number;
    leaderboard: LeaderboardEntry[];
  }>();

  private gameQuestionChanged$ = new Subject<{
    questionIndex: number;
    question: any;
    timeLeft: number;
  }>();

  private gamePhaseChanged$ = new Subject<{
    phase: 'question' | 'feedback' | 'results';
    data: any;
  }>();

  private gameTimerUpdate$ = new Subject<{
    timeLeft: number;
    phase: string;
  }>();

  constructor(
    private http: HttpClient,
    private zone: NgZone,
    private mercureService: MercureService
  ) {


    this.mercureService.invitationReceived$.subscribe(invitation => {

      this.invitations$.next(invitation);
    });
  }

  getAvailableRooms(): Observable<GameRoom[]> {
    return this.availableRooms$.asObservable();
  }

  getCurrentRoom(): Observable<GameRoom | null> {
    return this.currentRoom$.asObservable();
  }

  getCurrentGame(): Observable<MultiplayerGame | null> {
    return this.currentGame$.asObservable();
  }

  getInvitations(): Observable<GameInvitation> {
    return this.invitations$.asObservable();
  }

  getPlayerAnswered(): Observable<{
    userId: number;
    username: string;
    isCorrect: boolean;
    points: number;
    leaderboard: LeaderboardEntry[];
  }> {
    return this.playerAnswered$.asObservable();
  }

  getGameQuestionChanged(): Observable<{
    questionIndex: number;
    question: any;
    timeLeft: number;
  }> {
    return this.gameQuestionChanged$.asObservable();
  }

  getGamePhaseChanged(): Observable<{
    phase: 'question' | 'feedback' | 'results';
    data: any;
  }> {
    return this.gamePhaseChanged$.asObservable();
  }

  getGameTimerUpdate(): Observable<{
    timeLeft: number;
    phase: string;
  }> {
    return this.gameTimerUpdate$.asObservable();
  }


  setCurrentRoom(room: GameRoom | null): void {
    this.currentRoom$.next(room);
  }

  setCurrentGame(game: MultiplayerGame | null): void {
    this.currentGame$.next(game);
  }

  createRoom(quizId: number, maxPlayers = 4, isTeamMode = false, roomName?: string): Observable<GameRoom> {
    return this.http.post<GameRoom>(`${this.apiUrl}/multiplayer/room/create`, { quizId, maxPlayers, isTeamMode, roomName });
  }

  joinRoom(roomId: string, teamName?: string): Observable<GameRoom> {
    return this.http.post<GameRoom>(`${this.apiUrl}/multiplayer/room/${roomId}/join`, { teamName });
  }

  leaveRoom(roomId: string): Observable<GameRoom> {
    return this.http.post<GameRoom>(`${this.apiUrl}/multiplayer/room/${roomId}/leave`, {});
  }

  startGame(roomId: string): Observable<MultiplayerGame> {


    return this.http.post<MultiplayerGame>(`${this.apiUrl}/multiplayer/room/${roomId}/start`, {});
  }

  submitAnswer(gameId: string, questionId: number, answer: any, timeSpent: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/multiplayer/game/${gameId}/answer`, { questionId, answer, timeSpent });
  }

  submitPlayerScores(gameId: string, playerScores: { [username: string]: number }): Observable<any> {
    return this.http.post(`${this.apiUrl}/multiplayer/game/${gameId}/scores`, { playerScores });
  }

  getRoomStatus(roomId: string): Observable<GameRoom> {
    return this.http.get<GameRoom>(`${this.apiUrl}/multiplayer/room/${roomId}`);
  }

  getGameStatus(gameId: string): Observable<MultiplayerGame> {
    return this.http.get<MultiplayerGame>(`${this.apiUrl}/multiplayer/game/${gameId}/status`);
  }

  loadAvailableRooms(): Observable<GameRoom[]> {
    return this.http.get<GameRoom[]>(`${this.apiUrl}/multiplayer/rooms/available`);
  }

  sendInvitation(roomId: string, invitedUserIds: number[]): Observable<{ success: boolean }> {
    return this.http.post<{ success: boolean }>(`${this.apiUrl}/multiplayer/invite/${roomId}`, { invitedUserIds });
  }

  triggerFeedbackPhase(gameId: string): Observable<{ success: boolean }> {
    return this.http.post<{ success: boolean }>(`${this.apiUrl}/multiplayer/game/${gameId}/trigger-feedback`, {});
  }

  triggerNextQuestion(gameId: string): Observable<{ success: boolean }> {
    return this.http.post<{ success: boolean }>(`${this.apiUrl}/multiplayer/game/${gameId}/next-question`, {});
  }

  endGame(gameId: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/multiplayer/game/${gameId}/end`, {});
  }

  getCompanyGroupsForMultiplayer(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/multiplayer/groups/company`);
  }
  getCompanyMembersForMultiplayer(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/multiplayer/members/company`);
  }

  private getCurrentUserId(): number | null {
    const token = localStorage.getItem('JWT_TOKEN');
    if (!token) return null;
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      const userId = payload.userId || payload.sub || payload.id || payload.user_id;
      return userId ? parseInt(userId, 10) : null;
    } catch {
      return null;
    }
  }

  isPlayerInRoom(room: GameRoom, userId?: number): boolean {
    const id = userId ?? this.getCurrentUserId();
    return id != null && room.players.some(p => p.id === id);
  }

  isRoomCreator(room: GameRoom, userId?: number): boolean {
    const id = userId ?? this.getCurrentUserId();
    return id != null && room.creator.id === id;
  }

  canStartGame(room: GameRoom): boolean {
    return room.players.length >= 2 &&
      room.status === 'waiting' &&
      this.isRoomCreator(room);
  }
}
