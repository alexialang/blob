import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { MultiplayerService, GameInvitation } from '../../services/multiplayer.service';
import { MercureService } from '../../services/mercure.service';
import { Subscription } from 'rxjs';
import { trigger, state, style, transition, animate } from '@angular/animations';

@Component({
  selector: 'app-game-invitation-toast',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="invitation-container">
      <div
        *ngFor="let invitation of activeInvitations; trackBy: trackByRoomId"
        class="invitation-toast"
        [@slideIn]
      >
        <div class="invitation-header">
          <div class="invitation-icon">🎮</div>
          <div class="invitation-title">Invitation à jouer</div>
          <button
            class="close-btn"
            (click)="dismissInvitation(invitation.roomId)"
            aria-label="Fermer"
          >
            ×
          </button>
        </div>

        <div class="invitation-content">
          <p class="sender">
            <strong>{{ invitation.senderName }}</strong> vous invite à jouer
          </p>
          <p class="quiz-title">{{ invitation.quiz.title }}</p>
          <div class="quiz-info">
            <span class="players-count">
              {{ invitation.currentPlayers }}/{{ invitation.maxPlayers }} joueurs
            </span>
            <span class="questions-count"> {{ invitation.quiz.questionCount }} questions </span>
          </div>
        </div>

        <div class="invitation-actions">
          <button class="btn-decline" (click)="dismissInvitation(invitation.roomId)">
            Ignorer
          </button>
          <button class="btn-accept" (click)="acceptInvitation(invitation)">Rejoindre</button>
        </div>

        <div class="invitation-timer">
          <div class="timer-bar" [style.animation-duration]="timerDuration + 's'"></div>
        </div>
      </div>
    </div>
  `,
  styleUrls: ['./game-invitation-toast.component.scss'],
  animations: [
    trigger('slideIn', [
      transition(':enter', [
        style({ transform: 'translateX(100%)', opacity: 0 }),
        animate('300ms ease-out', style({ transform: 'translateX(0)', opacity: 1 })),
      ]),
      transition(':leave', [
        animate('200ms ease-in', style({ transform: 'translateX(100%)', opacity: 0 })),
      ]),
    ]),
  ],
})
export class GameInvitationToastComponent implements OnInit, OnDestroy {
  activeInvitations: GameInvitation[] = [];
  private subscription?: Subscription;
  private timers: Map<string, ReturnType<typeof setTimeout>> = new Map();

  readonly timerDuration = 15; // 15 secondes pour répondre

  constructor(
    private multiplayerService: MultiplayerService,
    private mercureService: MercureService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.mercureService.connect();

    this.subscription = this.mercureService.invitationReceived$.subscribe(invitation => {
      this.addInvitation(invitation);
    });
  }

  ngOnDestroy(): void {
    this.subscription?.unsubscribe();
    this.clearAllTimers();
    this.mercureService.disconnect();
  }

  private addInvitation(invitation: GameInvitation): void {
    if (this.activeInvitations.some(inv => inv.roomId === invitation.roomId)) {
      return;
    }

    this.activeInvitations.push(invitation);

    const timer = setTimeout(() => {
      this.dismissInvitation(invitation.roomId);
    }, this.timerDuration * 1000);

    this.timers.set(invitation.roomId, timer);
  }

  acceptInvitation(invitation: GameInvitation): void {
    this.dismissInvitation(invitation.roomId);

    this.multiplayerService.joinRoom(invitation.roomId).subscribe({
      next: room => {
        this.multiplayerService.setCurrentRoom(room);

        this.mercureService.disconnect();
        setTimeout(() => {
          this.mercureService.connect();
        }, 100);

        this.router.navigate(['/multiplayer/room', invitation.roomId]);
      },
      error: error => {
        console.error('Erreur lors de la jointure:', error);
      },
    });
  }

  dismissInvitation(roomId: string): void {
    this.activeInvitations = this.activeInvitations.filter(inv => inv.roomId !== roomId);

    const timer = this.timers.get(roomId);
    if (timer) {
      clearTimeout(timer);
      this.timers.delete(roomId);
    }
  }

  private clearAllTimers(): void {
    this.timers.forEach(timer => clearTimeout(timer));
    this.timers.clear();
  }

  trackByRoomId(index: number, invitation: GameInvitation): string {
    return invitation.roomId;
  }
}
