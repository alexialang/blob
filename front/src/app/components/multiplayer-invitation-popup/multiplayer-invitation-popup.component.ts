import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { MultiplayerService, GameInvitation } from '../../services/multiplayer.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-multiplayer-invitation-popup',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div 
      *ngFor="let invitation of invitations; trackBy: trackByRoomId" 
      class="invitation-popup"

    >
      <div class="invitation-content">
        <div class="invitation-header">
          <h3>🎮 Invitation de jeu</h3>
          <button class="close-btn" (click)="dismissInvitation(invitation)">×</button>
        </div>
        
        <div class="invitation-body">
          <p><strong>{{ invitation.senderName }}</strong> vous invite à rejoindre :</p>
          <div class="quiz-info">
            <h4>{{ invitation.quiz.title }}</h4>
            <p>{{ invitation.quiz.questionCount }} questions</p>
            <p>{{ invitation.currentPlayers }}/{{ invitation.maxPlayers }} joueurs</p>
          </div>
        </div>
        
        <div class="invitation-actions">
          <button class="btn btn-accept" (click)="acceptInvitation(invitation)">
            ✅ Accepter
          </button>
          <button class="btn btn-decline" (click)="dismissInvitation(invitation)">
            ❌ Refuser
          </button>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .invitation-popup {
      position: fixed;
      top: 20px;
      right: 20px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
      border: 2px solid #4CAF50;
      max-width: 350px;
      z-index: 9999;
      margin-bottom: 10px;
    }

    .invitation-content {
      padding: 20px;
    }

    .invitation-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .invitation-header h3 {
      margin: 0;
      color: #2E7D32;
      font-size: 16px;
    }

    .close-btn {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      color: #666;
    }

    .invitation-body p {
      margin: 0 0 10px 0;
    }

    .quiz-info {
      background: #f5f5f5;
      padding: 10px;
      border-radius: 8px;
      margin: 10px 0;
    }

    .quiz-info h4 {
      margin: 0 0 5px 0;
      color: #333;
      font-size: 14px;
    }

    .quiz-info p {
      margin: 2px 0;
      font-size: 12px;
      color: #666;
    }

    .invitation-actions {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }

    .btn {
      flex: 1;
      padding: 8px 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 500;
    }

    .btn-accept {
      background: #4CAF50;
      color: white;
    }

    .btn-decline {
      background: #f44336;
      color: white;
    }

    .btn:hover {
      opacity: 0.9;
    }
  `],
  animations: [
  ]
})
export class MultiplayerInvitationPopupComponent implements OnInit, OnDestroy {
  invitations: GameInvitation[] = [];
  private subscription?: Subscription;

  constructor(
    private multiplayerService: MultiplayerService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.subscription = this.multiplayerService.getInvitations().subscribe(
      invitation => {

        
        const exists = this.invitations.some(inv => inv.roomId === invitation.roomId);
        if (!exists) {
          this.invitations.push(invitation);
          
          setTimeout(() => {
            this.dismissInvitation(invitation);
          }, 30000);
        }
      }
    );
  }

  ngOnDestroy(): void {
    this.subscription?.unsubscribe();
  }

  acceptInvitation(invitation: GameInvitation): void {
    this.multiplayerService.joinRoom(invitation.roomId).subscribe({
      next: (room) => {
        this.multiplayerService.setCurrentRoom(room);
        this.router.navigate(['/multiplayer/room', invitation.roomId]);
        this.dismissInvitation(invitation);
      },
      error: (error) => {
        console.error('Erreur rejoindre salon:', error);
        alert('Impossible de rejoindre le salon. Il est peut-être plein ou fermé.');
        this.dismissInvitation(invitation);
      }
    });
  }

  dismissInvitation(invitation: GameInvitation): void {
    this.invitations = this.invitations.filter(inv => inv.roomId !== invitation.roomId);
  }

  trackByRoomId(index: number, invitation: GameInvitation): string {
    return invitation.roomId;
  }
}
