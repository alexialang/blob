import { Component, OnInit, OnDestroy, ElementRef, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { MultiplayerService, GameRoom, GamePlayer } from '../../services/multiplayer.service';
import { QuizTransitionService } from '../../services/quiz-transition.service';
import { MercureService } from '../../services/mercure.service';
import { AnalyticsService } from '../../services/analytics.service';
import { Subscription } from 'rxjs';
import { trigger, state, style, transition, animate } from '@angular/animations';

@Component({
  selector: 'app-multiplayer-room',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './multiplayer-room.component.html',
  styleUrls: ['./multiplayer-room.component.scss'],
  animations: [
    trigger('playerSlide', [
      transition(':enter', [
        style({ transform: 'translateY(-20px)', opacity: 0 }),
        animate('300ms ease-out', style({ transform: 'translateY(0)', opacity: 1 }))
      ])
    ])
  ]
})
export class MultiplayerRoomComponent implements OnInit, OnDestroy {
  currentRoom: GameRoom | null = null;
  starting = false;
  private subscriptions: Subscription[] = [];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private multiplayerService: MultiplayerService,
    private quizTransitionService: QuizTransitionService,
    private mercureService: MercureService,
    private elementRef: ElementRef,
    private cdr: ChangeDetectorRef,
    private analytics: AnalyticsService
  ) {}

  ngOnInit(): void {
    const roomId = this.route.snapshot.params['id'];

    this.analytics.trackMultiplayerRoomJoin();

    sessionStorage.setItem('currentRoomId', roomId);

    this.mercureService.disconnectFromGame();
    setTimeout(() => {
      this.mercureService.connect();
    }, 100);

    const roomSub = this.multiplayerService.getCurrentRoom().subscribe(
      room => {
        if (room === null && this.currentRoom !== null) {
          return;
        }

        this.currentRoom = room;

        this.cdr.detectChanges();

        if (room?.status === 'playing' && room.gameId) {
          this.starting = false;
          setTimeout(() => {
            this.router.navigate(['/multiplayer/game', room.gameId]);
          }, 100);
        }
      }
    );

    const mercureSub = this.mercureService.roomUpdated$.subscribe(
      roomData => {
        if (roomData.id === roomId) {
          this.currentRoom = roomData;
          this.multiplayerService.setCurrentRoom(roomData);
        }
      }
    );

    const gameEventSub = this.mercureService.gameEvent$.subscribe(
      event => {
        if (event.action === 'navigate_to_game' && event.gameId) {
          this.starting = false;
          this.router.navigate(['/multiplayer/game', event.gameId]);
        }
      }
    );

    this.subscriptions.push(roomSub, mercureSub, gameEventSub);

    this.loadRoom(roomId);

    const reconnectInterval = setInterval(() => {
      if (!this.mercureService.isConnected()) {
        this.mercureService.connect();
      }
    }, 5000);

    this.subscriptions.push({
      unsubscribe: () => clearInterval(reconnectInterval)
    } as any);
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
    this.mercureService.disconnect();
  }

  private loadRoom(roomId: string): void {
    this.multiplayerService.getRoomStatus(roomId).subscribe({
      next: (room) => {
        this.multiplayerService.setCurrentRoom(room);
      },
      error: (error) => {
        console.error('Erreur chargement salon:', error);
        this.router.navigate(['/quiz']);
      }
    });
  }

  async startGame(): Promise<void> {

    if (!this.currentRoom || this.starting) {
      return;
    }

    this.starting = true;

    this.multiplayerService.startGame(this.currentRoom.id).subscribe({
      next: (game) => {
        this.starting = false;
        this.router.navigate(['/multiplayer/game', game.id]);
      },
      error: (error) => {
        console.error(' Erreur lancement jeu - Détail complet:', error);
        this.starting = false;
      }
    });
  }

  leaveRoom(): void {
    if (!this.currentRoom) return;

    this.multiplayerService.leaveRoom(this.currentRoom.id).subscribe({
      next: () => {
        this.multiplayerService.setCurrentRoom(null);
        this.router.navigate(['/quiz']);
      },
      error: (error) => {
        console.error('Erreur quitter salon:', error);
        this.router.navigate(['/quiz']);
      }
    });
  }

  getTeamPlayers(team: string): GamePlayer[] {
    if (!this.currentRoom) return [];
    return this.currentRoom.players.filter(p => p.team === team);
  }

  getMaxPlayersPerTeam(): number {
    if (!this.currentRoom) return 0;
    return Math.floor(this.currentRoom.maxPlayers / 2);
  }

  getPlayerInitials(username: string): string {
    return username
      .split(' ')
      .map(name => name.charAt(0))
      .join('')
      .toUpperCase()
      .substring(0, 2);
  }

  getEmptySlots(): any[] {
    if (!this.currentRoom) return [];
    const empty = this.currentRoom.maxPlayers - this.currentRoom.players.length;
    return Array(empty).fill(null);
  }

  getStatusText(status: string): string {
    switch (status) {
      case 'waiting': return 'En attente';
      case 'playing': return 'En cours';
      case 'finished': return 'Terminé';
      default: return status;
    }
  }

  canStartGame(): boolean {
    if (!this.currentRoom) return false;
    return this.multiplayerService.canStartGame(this.currentRoom);
  }

  isCreator(): boolean {
    if (!this.currentRoom) return false;
    return this.multiplayerService.isRoomCreator(this.currentRoom);
  }
}
