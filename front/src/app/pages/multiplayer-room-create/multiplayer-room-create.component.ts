import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MultiplayerService } from '../../services/multiplayer.service';
import { QuizManagementService } from '../../services/quiz-management.service';
import { CompanyService } from '../../services/company.service';
import { UserService } from '../../services/user.service';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';

@Component({
  selector: 'app-multiplayer-room-create',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './multiplayer-room-create.component.html',
  styleUrls: ['./multiplayer-room-create.component.scss']
})
export class MultiplayerRoomCreateComponent implements OnInit {
  quizData: any = null;
  loading = true;
  creating = false;

  roomName = '';
  maxPlayers = 4;
  isTeamMode = false;

  invitationType: 'group' | 'users' = 'group';
  availableGroups: any[] = [];
  availableUsers: any[] = [];
  selectedGroupId: number | null = null;
  selectedUserIds: number[] = [];
  currentUser: any = null;

  highlightColor: string = '';

  private destroy$ = new Subject<void>();
  private company: any;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private multiplayerService: MultiplayerService,
    private quizService: QuizManagementService,
    private companyService: CompanyService,
    private userService: UserService,
  ) {}

  ngOnInit(): void {
    const quizId = +this.route.snapshot.params['id'];
    this.loadQuiz(quizId);
    this.loadUserData();
    this.generateRandomColor();

  }
  private loadQuiz(quizId: number): void {
    this.quizService.getQuiz(quizId).subscribe({
      next: (quiz: any) => {
        this.quizData = quiz;
        this.loading = false;
      },
      error: (error: any) => {
        console.error('Erreur chargement quiz:', error);
        this.goBack();
      }
    });
  }

  private loadUserData(): void {
    this.userService.getUserProfile().subscribe({
      next: (user) => {
        this.currentUser = user;
        if (user.companyId) {
          this.loadCompany(user.companyId);
        }
      },
      error: (error) => {
      }
    });
  }

  private loadCompany(companyId: number): void {
    this.multiplayerService.getCompanyGroupsForMultiplayer()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (groups: any[]) => {
          this.availableGroups = Array.isArray(groups) ? groups : [];
        },
        error: (error: any) => {
          this.availableGroups = [];
        }
      });

    this.multiplayerService.getCompanyMembersForMultiplayer()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {

          let users: any[] = [];
          if (response && response.success && Array.isArray(response.data)) {
            users = response.data;
          } else if (Array.isArray(response)) {
            users = response;
          } else {
            console.error('Format de réponse inattendu:', response);
            users = [];
          }

          if (Array.isArray(users)) {
            this.availableUsers = users.filter((user: any) => user.id !== this.currentUser?.id);

            const maxPossiblePlayers = this.availableUsers.length + 1;
            if (this.maxPlayers > maxPossiblePlayers) {
              this.maxPlayers = Math.min(maxPossiblePlayers, 8);
            }
          } else {
            console.error('La réponse API n\'est pas un tableau:', users);
            this.availableUsers = [];
          }
        },
        error: (error: any) => {
          console.error('Erreur lors du chargement des utilisateurs:', error);
          this.availableUsers = [];
        }
      });
  }

  createRoom(): void {
    if (!this.quizData || this.creating) return;

    this.creating = true;

    this.multiplayerService.createRoom(
      this.quizData.id,
      this.maxPlayers,
      this.isTeamMode,
      this.roomName || undefined
    ).subscribe({
      next: (room) => {

        this.multiplayerService.setCurrentRoom(room);

        this.sendInvitations(room.id);

        this.router.navigate(['/multiplayer/room', room.id]);
      },
      error: (error) => {
        this.creating = false;
        alert('Erreur lors de la création du salon: ' + (error.error?.error || error.message || 'Erreur inconnue'));
      }
    });
  }

  private sendInvitations(roomId: string): void {
    let userIdsToInvite: number[] = [];

    if (this.invitationType === 'group' && this.selectedGroupId) {
      const selectedGroup = this.availableGroups.find(g => g.id === this.selectedGroupId);
      if (selectedGroup && selectedGroup.users) {
        userIdsToInvite = selectedGroup.users
          .filter((user: any) => user.id !== this.currentUser?.id)
          .map((user: any) => user.id);
      }
    } else if (this.invitationType === 'users') {

      userIdsToInvite = [...this.selectedUserIds];
    }

    if (userIdsToInvite.length > 0) {
      this.multiplayerService.sendInvitation(roomId, userIdsToInvite).subscribe({
        next: () => {
          console.log(`Invitations envoyées à ${userIdsToInvite.length} utilisateur(s)`);
        },
        error: (error) => {
          console.error('Erreur lors de l\'envoi des invitations:', error);
        }
      });
    }
  }

  getCurrentUsername(): string {
    if (this.currentUser) {
      return this.currentUser.pseudo || this.currentUser.firstName || 'Joueur';
    }

    const token = localStorage.getItem('JWT_TOKEN');
    if (token) {
      try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        return payload.username || payload.pseudo || payload.sub || 'Joueur';
      } catch {
        return 'Joueur';
      }
    }

    return 'Joueur';
  }

  goBack(): void {
    this.router.navigate(['/quiz']);
  }

  onInvitationTypeChange(): void {
    this.selectedGroupId = null;
    this.selectedUserIds = [];
  }

  onGroupChange(): void {
    if (this.selectedGroupId) {
      const selectedGroup = this.availableGroups.find(g => g.id === this.selectedGroupId);
      if (selectedGroup && selectedGroup.users) {
        this.selectedUserIds = selectedGroup.users
          .filter((user: any) => user.id !== this.currentUser?.id)
          .map((user: any) => user.id);

        this.maxPlayers = Math.min(this.selectedUserIds.length + 1, 8);

      }
    } else {

      this.selectedUserIds = [];
    }
  }

  onUserToggle(userId: number): void {
    const index = this.selectedUserIds.indexOf(userId);
    if (index > -1) {
      this.selectedUserIds.splice(index, 1);
    } else {
      if (this.selectedUserIds.length < this.maxPlayers - 1) {
        this.selectedUserIds.push(userId);
      }
    }
  }

  isUserSelected(userId: number): boolean {
    return this.selectedUserIds.includes(userId);
  }

  getSelectedUsersCount(): number {
    return this.selectedUserIds.length + 1;
  }

  canCreateRoom(): boolean {
    if (this.invitationType === 'group') {
      if (this.selectedGroupId !== null) {
        const selectedGroup = this.availableGroups.find(g => g.id === this.selectedGroupId);
        return selectedGroup && selectedGroup.users && selectedGroup.users.length > 0;
      }
      return false;
    } else {
      return this.selectedUserIds.length > 0;
    }
  }
  private generateRandomColor(): void {
    const colors = [
      '#257D54', '#FAA24B', '#D30D4C',
    ];

    const index = Math.floor(Math.random() * colors.length);
    this.highlightColor = colors[index];
  }
}
