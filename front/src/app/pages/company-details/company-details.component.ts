import {
  Component,
  OnInit,
  OnDestroy,
  ViewChild,
  ChangeDetectorRef
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import {  Subject, takeUntil, catchError, of } from 'rxjs';
import { GlobalStatisticsComponent } from '../../components/global-statistics/global-statistics.component';
import { CompanyService } from '../../services/company.service';
import { AuthService } from '../../services/auth.service';
import { PaginationComponent } from '../../components/pagination/pagination.component';
import { AddMemberModalComponent } from '../../components/add-member-modal/add-member-modal.component';
import { TuiDialogService, TuiAlertService } from '@taiga-ui/core';
import { TuiTable } from '@taiga-ui/addon-table';
import { TuiButton } from '@taiga-ui/core';
import { HasPermissionDirective } from '../../directives/has-permission.directive';

interface Collaborator {
  id: number;
  name: string;
  email: string;
  isActive: boolean;
  groups: string[];
  rights: any[];
  quizs?: any[];
  userAnswers?: any[];
  badges?: any[];
}

interface Company {
  id: number | null;
  name: string;
  users?: any[];
  groups?: any[];
}

@Component({
  selector: 'app-company-details',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    PaginationComponent,
    AddMemberModalComponent,
    GlobalStatisticsComponent,
    TuiTable,
    TuiButton,
    HasPermissionDirective,
  ],
  templateUrl: './company-details.component.html',
  styleUrls: ['./company-details.component.scss']
})
export class CompanyDetailsComponent implements OnInit, OnDestroy {
  @ViewChild(GlobalStatisticsComponent) globalStats?: GlobalStatisticsComponent;

  company: Company | null = null;
  allCollaborators: Collaborator[] = [];
  filteredCollaborators: Collaborator[] = [];
  availableMembersForNewGroup: any[] = [];
  groupList: string[] = [];

  searchKeyword = '';
  filterByGroup = '';
  filterByStatus = '';
  sortColumn: keyof Collaborator = 'name';
  sortDirection: 'asc' | 'desc' = 'asc';

  page = 1;
  pageSize = 10;
  loadError = false;
  canViewStats = false;

  showAddMemberModal = false;
  showCreateGroupModal = false;
  newGroupName = '';
  newGroupDescription = '';
  selectedMembersForGroup: number[] = [];
  selectedGroupId: number | null = null;
  availableMembersForGroup: any[] = [];
  selectedMemberForGroup: number | null = null;
  showAddMemberToGroupModal = false;

  highlightColor: string = '';
  activeTabIndex = 0;

  private destroy$ = new Subject<void>();

  constructor(
    private readonly route: ActivatedRoute,
    private readonly router: Router,
    private readonly companyService: CompanyService,
    private readonly authService: AuthService,
    private readonly cdr: ChangeDetectorRef,
    private readonly alertService: TuiAlertService,
    private readonly dialogService: TuiDialogService
  ) {}

  ngOnInit(): void {
    this.generateRandomColor();
    const companyId = Number(this.route.snapshot.paramMap.get('id'));
    if (companyId) {
      this.loadCompany(companyId);
    }
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private loadCompany(companyId: number): void {
    this.checkUserPermissions();
    this.loadCompanyFull(companyId);
  }

  refreshCompanyData(): void {
    const companyId = Number(this.route.snapshot.paramMap.get('id'));
    if (companyId) {
      this.loadCompany(companyId);
    }
  }

  private loadCompanyFull(companyId: number): void {
    this.companyService.getCompany(companyId)
      .pipe(
        takeUntil(this.destroy$),
        catchError((error) => {
          this.loadError = true;
          return of(null);
        })
      )
      .subscribe((data: any) => {
        if (!data || !data.success) {
          return;
        }
        this.company = data.data;
        if (this.company && this.company.groups) {
          this.company.groups.forEach((group: any) => group.expanded = false);
        }
        this.prepareCollaborators();
        if (this.company && data.data.users) {
          this.allCollaborators = this.allCollaborators.map(collab => {
            const fullUser = data.data.users.find((u: any) => u.id === collab.id);
            return {
              ...collab,
              quizs: fullUser?.quizs ?? [],
              userAnswers: fullUser?.userAnswers ?? [],
              badges: fullUser?.badges ?? []
            };
          });
        }

        this.cdr.markForCheck();
      });
  }

  private loadCompanyBasic(companyId: number): void {
    this.companyService.getCompanyBasic(companyId)
      .pipe(
        takeUntil(this.destroy$),
        catchError((error) => {
          this.loadError = true;
          return of(null);
        })
      )
      .subscribe((data: any) => {
        if (!data || !data.success) {
          return;
        }
        this.company = data.data;
        if (this.company && this.company.groups) {
          this.company.groups.forEach((group: any) => group.expanded = false);
        }
        this.prepareCollaborators();

        this.cdr.markForCheck();
      });
  }

  private checkUserPermissions(): void {
    this.authService.hasPermission('VIEW_RESULTS').subscribe({
      next: (hasPermission) => {
        this.canViewStats = hasPermission;
        this.cdr.markForCheck();
      },
      error: (error) => {
        this.canViewStats = false;
        this.cdr.markForCheck();
      }
    });
  }

  private prepareCollaborators(): void {
    if (!this.company) return;
    if (!this.company.users) this.company.users = [];
    if (!this.company.groups) this.company.groups = [];

    const groupNameMap = new Map<number, string>();
    this.company.groups.forEach((group: any) => groupNameMap.set(group.id, group.name));

    const collaborators: Collaborator[] = this.company.users.map((user: any) => {
      const groupNames = Array.isArray(user.groups)
        ? user.groups.map((g: any) => g.name || g)
        : user.groupId && groupNameMap.has(user.groupId)
          ? [groupNameMap.get(user.groupId)!]
          : [];

      return {
        id: user.id,
        name: `${user.firstName || ''} ${user.lastName || ''}`.trim() || user.email,
        email: user.email,
        isActive: user.isActive ?? true,
        groups: groupNames,
        rights: user.permissions || user.userPermissions || [],
      };
    });

    this.groupList = [...new Set(collaborators.flatMap(c => c.groups))].sort();
    this.allCollaborators = collaborators;
    this.filteredCollaborators = [...collaborators];
    this.availableMembersForNewGroup = [...this.company.users];
    this.applyFilters();
  }

  applyFilters(): void {
    let result = this.allCollaborators;

    if (this.filterByGroup) {
      result = result.filter(c => c.groups.includes(this.filterByGroup));
    }

    if (this.filterByStatus) {
      const active = this.filterByStatus === 'active';
      result = result.filter(c => c.isActive === active);
    }

    if (this.searchKeyword) {
      const keyword = this.searchKeyword.toLowerCase();
      result = result.filter(c =>
        c.name.toLowerCase().includes(keyword) ||
        c.email.toLowerCase().includes(keyword)
      );
    }

    this.filteredCollaborators = result;
    this.page = 1;
    this.cdr.markForCheck();
  }

  sortBy(column: keyof Collaborator): void {
    this.sortDirection = this.sortColumn === column
      ? (this.sortDirection === 'asc' ? 'desc' : 'asc')
      : 'asc';
    this.sortColumn = column;

    this.filteredCollaborators.sort((a: any, b: any) => {
      const av = a[column];
      const bv = b[column];
      if (typeof av === 'string') return this.sortDirection === 'asc' ? av.localeCompare(bv as string) : (bv as string).localeCompare(av);
      if (typeof av === 'boolean') return this.sortDirection === 'asc' ? (+av) - (+bv) : (+bv) - (+av);
      return 0;
    });

    this.cdr.markForCheck();
  }

  get pagedCollaborators(): Collaborator[] {
    const start = (this.page - 1) * this.pageSize;
    return this.filteredCollaborators.slice(start, start + this.pageSize);
  }

  get totalPages(): number {
    return Math.ceil(this.filteredCollaborators.length / this.pageSize);
  }

  onPageChange(newPage: number): void {
    this.page = newPage;
  }

  private generateRandomColor(): void {
    const colors = [
      'var(--color-primary)',
      'var(--color-secondary)',
      'var(--color-accent)',
      'var(--color-pink)'
    ];
    const randomColor = colors[Math.floor(Math.random() * colors.length)];
    this.highlightColor = randomColor;
  }

  toggleGroupExpansion(groupId: number): void {
    const group = this.company?.groups?.find(g => g.id === groupId);
    if (group) {
      group.expanded = !group.expanded;
      this.cdr.markForCheck();
    }
  }

  showAddMemberModalAction(): void {
    this.showAddMemberModal = true;
  }

  hideAddMemberModal(): void {
    this.showAddMemberModal = false;
  }

  onMemberAdded(member: any): void {
    this.hideAddMemberModal();
    if (this.company?.id) {
      this.loadCompany(this.company.id);
    }
  }

  onModalClosed(): void {
    this.hideAddMemberModal();
  }

  showCreateGroupModalAction(): void {
    this.showCreateGroupModal = true;
    if (this.company?.users) {
      this.availableMembersForNewGroup = [...this.company.users];
    }
  }

  hideCreateGroupModal(): void {
    this.showCreateGroupModal = false;
    this.newGroupName = '';
    this.newGroupDescription = '';
    this.selectedMembersForGroup = [];
  }

  toggleMemberSelection(memberId: number): void {
    const index = this.selectedMembersForGroup.indexOf(memberId);
    if (index > -1) {
      this.selectedMembersForGroup.splice(index, 1);
    } else {
      this.selectedMembersForGroup.push(memberId);
    }
  }

  createGroup(): void {
    if (!this.newGroupName.trim()) {
      this.alertService.open('Le nom du groupe est requis !').subscribe();
      return;
    }

    if (!this.company?.id) {
      this.alertService.open('ID de l\'entreprise manquant').subscribe();
      return;
    }

    const groupData = {
      name: this.newGroupName.trim(),
      description: this.newGroupDescription.trim(),
      userIds: this.selectedMembersForGroup
    };

    this.companyService.createGroup(this.company.id, groupData).subscribe({
      next: (response) => {
        this.alertService.open('Groupe créé avec succès !').subscribe();
        this.hideCreateGroupModal();
        this.loadCompany(this.company!.id!);
      },
      error: (error) => {
        this.alertService.open('Erreur lors de la création du groupe').subscribe();
      }
    });
  }

  deleteGroup(groupId: number): void {
    if (!this.company?.id) {
      this.alertService.open('ID de l\'entreprise manquant').subscribe();
      return;
    }

    this.alertService.open('Êtes-vous sûr de vouloir supprimer ce groupe ?').subscribe();
    this.companyService.deleteGroup(this.company.id, groupId).subscribe({
      next: () => {
        this.alertService.open('Groupe supprimé avec succès !').subscribe();
        this.loadCompany(this.company!.id!);
      },
      error: (error) => {
        console.error('Erreur lors de la suppression du groupe:', error);
        this.alertService.open('Erreur lors de la suppression du groupe').subscribe();
      }
    });
  }

  removeUserFromGroup(groupId: number, userId: number): void {
    if (!this.company?.id) {
      this.alertService.open('ID de l\'entreprise manquant').subscribe();
      return;
    }

    this.alertService.open('Êtes-vous sûr de vouloir retirer cet utilisateur du groupe ?').subscribe();
    this.companyService.removeUserFromGroup(this.company.id, groupId, userId).subscribe({
      next: () => {
        this.alertService.open('Utilisateur retiré du groupe avec succès !').subscribe();
        this.loadCompany(this.company!.id!);
      },
      error: (error) => {
        console.error('Erreur lors du retrait de l\'utilisateur:', error);
        this.alertService.open('Erreur lors du retrait de l\'utilisateur').subscribe();
      }
    });
  }

  addUserToGroup(groupId: number, userId: number): void {
    if (!this.company?.id) {
      this.alertService.open('ID de l\'entreprise manquant').subscribe();
      return;
    }

    this.companyService.addUserToGroup(this.company.id, groupId, userId).subscribe({
      next: () => {
        this.alertService.open('Utilisateur ajouté au groupe avec succès !').subscribe();
        this.loadCompany(this.company!.id!);
      },
      error: (error) => {
        this.alertService.open('Erreur lors de l\'ajout de l\'utilisateur').subscribe();
      }
    });
  }

  addMemberToGroup(groupId: number): void {
    this.selectedGroupId = groupId;
    this.availableMembersForGroup = this.getAvailableMembersForGroup(groupId);
    this.showAddMemberToGroupModal = true;
  }

  selectAllMembers(): void {
    this.selectedMembersForGroup = this.availableMembersForNewGroup.map(member => member.id);
  }

  deselectAllMembers(): void {
    this.selectedMembersForGroup = [];
  }

  isMemberSelected(memberId: number): boolean {
    return this.selectedMembersForGroup.includes(memberId);
  }

  cancelCreateGroup(): void {
    this.hideCreateGroupModal();
  }

  confirmCreateGroup(): void {
    this.createGroup();
  }

  deleteCollaborator(collaboratorId: number): void {
    this.alertService.open('Êtes-vous sûr de vouloir supprimer ce collaborateur ?').subscribe();
    this.alertService.open('Fonctionnalité de suppression de collaborateur à implémenter').subscribe();
  }

  onAddMemberCancelled(): void {
    this.hideAddMemberModal();
  }

  getActiveUsersCount(): number {
    if (!this.company || !this.company.users) return 0;
    return this.company.users.filter(user => user.isActive).length;
  }

  toggleGroup(group: any): void {
    group.expanded = !group.expanded;
    this.cdr.markForCheck();
  }

  getCollaboratorStats(collaborator: any): string {
    return 'Voir plus';
  }

  editCompany(): void {

  }

  deleteCompany(): void {

  }

  // Nouvelles méthodes pour ajouter un membre à un groupe
  getAvailableMembersForGroup(groupId: number): any[] {
    if (!this.company?.users || !this.company?.groups) return [];
    
    const targetGroup = this.company.groups.find(g => g.id === groupId);
    if (!targetGroup) return [];
    
    // Récupérer les IDs des membres déjà dans le groupe
    const membersInGroup = new Set(targetGroup.users?.map((u: any) => u.id) || []);
    
    // Retourner les membres de l'entreprise qui ne sont pas encore dans ce groupe
    return this.company.users.filter(user => !membersInGroup.has(user.id));
  }

  selectMemberForGroup(memberId: number): void {
    this.selectedMemberForGroup = memberId;
  }

  confirmAddMemberToGroup(): void {
    if (!this.selectedMemberForGroup || !this.selectedGroupId || !this.company?.id) return;
    
    this.companyService.addUserToGroup(this.company.id, this.selectedGroupId, this.selectedMemberForGroup)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (result) => {
          this.alertService.open(`Membre ajouté au groupe avec succès !`, {
            appearance: 'success'
          }).subscribe();
          
          this.closeAddMemberToGroupModal();
          // Recharger les données
          if (this.company?.id) {
            this.loadCompany(this.company.id);
          }
        },
        error: (error) => {
          console.error('Erreur lors de l\'ajout du membre au groupe:', error);
          this.alertService.open(
            error.message || 'Erreur lors de l\'ajout du membre au groupe',
            { appearance: 'error' }
          ).subscribe();
        }
      });
  }

  closeAddMemberToGroupModal(): void {
    this.showAddMemberToGroupModal = false;
    this.selectedGroupId = null;
    this.selectedMemberForGroup = null;
    this.availableMembersForGroup = [];
  }
}
