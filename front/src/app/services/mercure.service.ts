import { Injectable, NgZone } from '@angular/core';
import { BehaviorSubject, Subject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class MercureService {
  private eventSource?: EventSource;
  private isConnected$ = new BehaviorSubject<boolean>(false);


  public invitationReceived$ = new Subject<any>();
  public roomUpdated$ = new Subject<any>();
  public gameEvent$ = new Subject<any>();
  public gameSync$ = new Subject<any>();

  constructor(private zone: NgZone) {
  
  }

  connect(): void {
  
    if (this.eventSource) {
      if (this.eventSource.readyState === EventSource.OPEN || 
          this.eventSource.readyState === EventSource.CONNECTING) {

        return;
      }
      
      this.eventSource.close();
    }



    
    this.cleanupOldRoomData();

    const token = localStorage.getItem('JWT_TOKEN');
    if (!token) {

      return;
    }

    const userId = this.getCurrentUserId();
    if (!userId) {
      console.error('❌ Pas d\'ID utilisateur');
      return;
    }



        this.disconnect();

    const topics = [
      `user-${userId}-invitation`,
      `user-${userId}-game-started`,
      'rooms-updated'
    ];

    const roomId = this.getCurrentRoomId();
    if (roomId) {
      topics.push(`room-${roomId}`);

    }

    const gameId = this.getCurrentGameId();
    if (gameId) {
      topics.push(`game-${gameId}`);
    }

    const url = `http://localhost:3000/.well-known/mercure?${topics.map(t => `topic=${t}`).join('&')}`;



    try {
      if (this.eventSource) {
        this.eventSource.close();
      }

      this.eventSource = new EventSource(url, {
        withCredentials: false
      });

      this.eventSource.onopen = () => {

        this.isConnected$.next(true);
      };

      this.eventSource.onmessage = (event) => {

        
        

        try {
          const data = JSON.parse(event.data);

          this.zone.run(() => this.handleMessage(data));
        } catch (err) {
          console.error('❌ Erreur parsing:', err);
          console.error('❌ Raw data:', event.data);
        }
      };

      this.eventSource.onerror = (error) => {
        console.error('❌ ERREUR MERCURE:', error);
        this.isConnected$.next(false);
        this.disconnect();
      };
    } catch (error) {
      console.error('❌ Erreur création EventSource:', error);
    }
  }

  private handleMessage(data: any): void {

    


    if (data.type === 'invitation') {
      this.invitationReceived$.next(data);
    }

    else if (data.roomId && data.senderName && data.quiz) {

      this.invitationReceived$.next(data);
    }
    else if (data.type === 'room_updated') {
      this.roomUpdated$.next(data);
    }
    else if (Array.isArray(data)) {
      const currentRoomId = this.getCurrentRoomId();
      if (currentRoomId) {
        const updatedRoom = data.find((room: any) => room.id === currentRoomId);
        if (updatedRoom) {

          this.roomUpdated$.next(updatedRoom);
        }
      }
    }

    else if (data.action === 'navigate_to_game') {

      this.gameEvent$.next(data);
    }

    else if (data.action === 'new_question') {

      this.gameSync$.next(data);
    }
    else if (data.action === 'player_answered') {

      this.gameSync$.next(data);
    }
    else if (data.action === 'show_feedback') {

      this.gameSync$.next(data);
    }
    else if (data.action === 'game_finished') {

      this.gameSync$.next(data);
    }
    else if (data.action === 'timer_update') {

      this.gameSync$.next(data);
    }
    else if (data.action || data.type === 'game_event') {
      this.gameSync$.next(data);
    }
    else {
      this.gameEvent$.next(data);
    }
  }

  disconnect(): void {
    if (this.eventSource) {
      this.eventSource.close();
      this.eventSource = undefined;
      this.isConnected$.next(false);
  
    }
  }

  isConnected(): boolean {
    return this.isConnected$.value;
  }

  private getCurrentUserId(): number | null {
    const token = localStorage.getItem('JWT_TOKEN');
    if (!token) return null;

    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      return payload.userId || payload.sub || payload.id;
    } catch {
      return null;
    }
  }

  private getCurrentGameId(): string | null {
    const path = window.location.pathname;
    const gameMatch = path.match(/\/multiplayer\/game\/([^\/]+)/);
    if (gameMatch) {
      let gameId = gameMatch[1];
      if (gameId.startsWith('game_')) {
        gameId = gameId.substring(5);
      }
      return gameId;
    }

    return null;
  }

  private getCurrentRoomId(): string | null {
    const path = window.location.pathname;
    const roomMatch = path.match(/\/multiplayer\/room\/([^\/]+)/);
    if (roomMatch) {
      return roomMatch[1];
    }

    const roomId = sessionStorage.getItem('currentRoomId');
    if (roomId) {
      
      return roomId;
    }

    return null;
  }

  connectWithGame(gameId: string): void {
    localStorage.setItem('current_game_id', gameId);

    this.connect();

    setTimeout(() => {
      if (!this.isConnected$.value) {

        this.connect();
      }
    }, 1000);
  }

  disconnectFromGame(): void {

    localStorage.removeItem('current_game_id');
    
    setTimeout(() => {
      this.connect(); // Reconnexion sans les topics de jeu
    }, 100);
  }



  private cleanupOldRoomData(): void {
    const currentPath = window.location.pathname;

    if (!currentPath.includes('/multiplayer/room/')) {
      const oldRoomId = sessionStorage.getItem('currentRoomId');
      if (oldRoomId) {

        sessionStorage.removeItem('currentRoomId');
      }
    }
  }
}

