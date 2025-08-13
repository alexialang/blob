import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MultiplayerService } from '../../services/multiplayer.service';
import { QuizManagementService } from '../../services/quiz-management.service';
import { CompanyManagementService } from '../../services/company-management.service';
import { UserService } from '../../services/user.service';

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

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private multiplayerService: MultiplayerService,
    private quizService: QuizManagementService,
    private companyService: CompanyManagementService,
    private userService: UserService
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
          this.loadCompanyData(user.companyId);
        }
      },
      error: (error) => {
        console.error('Erreur chargement profil:', error);
      }
    });
  }

  private loadCompanyData(companyId: number): void {
    this.companyService.getCompanyDetailed(companyId).subscribe({
      next: (company) => {
        this.availableGroups = company.groups || [];
        this.availableUsers = (company.users || []).filter((user: any) => user.id !== this.currentUser?.id);

        const maxPossiblePlayers = this.availableUsers.length + 1;
        if (this.maxPlayers > maxPossiblePlayers) {
          this.maxPlayers = Math.min(maxPossiblePlayers, 8);
        }
      },
      error: (error) => {
        console.error('Erreur chargement entreprise:', error);
      }
    });
  }

  createRoom(): void {
    if (!this.quizData || this.creating) return;

    this.creating = true;


    this.testAPI().then(() => {
      this.multiplayerService.createRoom(
        this.quizData.id,
        this.maxPlayers,
        this.isTeamMode,
        this.roomName || undefined
      ).subscribe({
        next: (room) => {
          this.multiplayerService.setCurrentRoom(room);

          if (this.selectedUserIds.length > 0) {
            this.multiplayerService.sendInvitation(room.id, this.selectedUserIds).subscribe({
              next: () => {

              },
              error: (error) => {
                console.error('Erreur envoi invitations:', error);
              }
            });
          }

          this.router.navigate(['/multiplayer/room', room.id]);
        },
        error: (error) => {
          console.error('Erreur création salon:', error);
          this.creating = false;
          alert('Erreur lors de la création du salon: ' + error.error?.error || error.message);
        }
      });
    }).catch(error => {
      console.error('Test API échoué:', error);
      this.creating = false;
      alert('API multiplayer non accessible');
    });
  }

  private testAPI(): Promise<any> {
    return fetch(`${this.quizService['apiUrl']}/multiplayer/test`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('JWT_TOKEN')}`
      }
    }).then(response => {
      if (!response.ok) throw new Error('API non accessible');
      return response.json();
    }).then(data => {

    });
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
      return this.selectedGroupId !== null;
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
