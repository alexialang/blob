import {
  Component,
  OnInit,
  ChangeDetectionStrategy,
  ChangeDetectorRef
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { catchError, of } from 'rxjs';
import { TuiDialogService, TuiAlertService } from '@taiga-ui/core';
import { TuiBadge, TuiTabsHorizontal } from '@taiga-ui/kit';
import { TuiTable, } from '@taiga-ui/addon-table';
import { TuiButton } from '@taiga-ui/core';
import { CompanyManagementService } from '../../services/company-management.service';
import { PaginationComponent } from '../../components/pagination/pagination.component';

interface Collaborator {
  id: number;
  name: string;
  email: string;
  isActive: boolean;
  groups: string[];
  rights: string[];
}

@Component({
  selector: 'app-company-details',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    PaginationComponent,
    TuiTabsHorizontal,
    TuiBadge,
    TuiTable,

    TuiButton,
  ],
  templateUrl: './company-details.component.html',
  styleUrls: ['./company-details.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CompanyDetailsComponent implements OnInit {
  company: any = null;
  loadError = false;
  activeTabIndex = 0;

  filterByGroup = '';
  filterByStatus = '';
  searchKeyword = '';
  groupList: string[] = [];

  page = 1;
  pageSize = 20;
  allCollaborators: Collaborator[] = [];
  filteredCollaborators: Collaborator[] = [];

  sortColumn: keyof Collaborator | '' = '';
  sortDirection: 'asc' | 'desc' = 'asc';

  showAddMemberModal = false;
  showCreateGroupModal = false;
  selectedGroupId: number | null = null;
  selectedUserId: number | null = null;
  availableMembersForNewGroup: any[] = [];
  newGroupName = '';
  newGroupDescription = '';
  selectedMemberIds: number[] = [];

  constructor(
    private readonly route: ActivatedRoute,
    private readonly router: Router,
    private readonly companyService: CompanyManagementService,
    private readonly cdr: ChangeDetectorRef,
    private readonly alertService: TuiAlertService,
    private readonly dialogService: TuiDialogService
  ) {}

  ngOnInit(): void {
    const companyId = Number(this.route.snapshot.paramMap.get('id'));
    if (companyId) this.loadCompany(companyId);
  }

  private loadCompany(companyId: number): void {
    this.companyService.getCompanyDetailed(companyId)
      .pipe(catchError(() => {
        this.loadError = true;
        return of(null);
      }))
      .subscribe(data => {
        if (!data) return;
        this.company = data;
        this.company.groups?.forEach((group: any) => group.expanded = false);
        this.prepareCollaborators();
        this.cdr.markForCheck();
      });
  }

  private prepareCollaborators(): void {
    const groupNameMap = new Map<number, string>();
    this.company.groups?.forEach((group: any) => groupNameMap.set(group.id, group.name));

    const collaborators: Collaborator[] = (this.company.users || []).map((user: any) => {
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
    this.availableMembersForNewGroup = [...this.company.users || []];
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

    this.filteredCollaborators.sort((a, b) => {
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

  onPageChange(newPage: number): void {
    this.page = newPage;
    this.cdr.markForCheck();
  }

  editCompany(): void {
    if (this.company) {
      this.router.navigate(['/admin/companies', this.company.id, 'edit']);
    }
  }

  deleteCompany(): void {
    if (!this.company) return;
    this.dialogService.open<boolean>('Confirmer la suppression de cette entreprise ?', {
      label: 'Suppression',
    }).subscribe(confirmed => {
      if (confirmed) {
        this.companyService.deleteCompany(this.company.id).subscribe({
          next: () => {
            this.alertService.open('Entreprise supprimée.');
            this.router.navigate(['/admin/companies']);
          },
          error: () => {
            this.alertService.open('Erreur lors de la suppression.');
          }
        });
      }
    });
  }


  deleteCollaborator(userId: number): void {
    this.dialogService.open<boolean>('Confirmer la suppression de ce collaborateur ?', {
      label: 'Suppression',
    }).subscribe(confirmed => {
      if (confirmed) {
        this.companyService.deleteUser(userId).subscribe({
          next: () => {
            this.alertService.open('Collaborateur supprimé.');
            this.loadCompany(this.company.id);
          },
          error: () => {
            this.alertService.open('Erreur lors de la suppression.');
          }
        });
      }
    });
  }

  toggleGroup(group: any): void {
    group.expanded = !group.expanded;
    this.cdr.markForCheck();
  }

  deleteGroup(groupId: number): void {
    this.dialogService.open<boolean>('Confirmer la suppression de ce groupe ?', {
      label: 'Suppression',
    }).subscribe(confirmed => {
      if (confirmed) {
        this.companyService.deleteGroup(groupId).subscribe({
          next: () => {
            this.alertService.open('Groupe supprimé.');
            this.loadCompany(this.company.id);
          },
          error: () => {
            this.alertService.open('Erreur lors de la suppression.');
          }
        });
      }
    });
  }

  removeFromGroup(groupId: number, userId: number): void {
    this.dialogService.open<boolean>('Retirer ce membre du groupe ?', {
      label: 'Confirmation',
    }).subscribe((confirmed) => {
      if (confirmed) {
        this.companyService.removeUserFromGroup(groupId, userId).subscribe({
          next: () => {
            this.alertService.open('Membre retiré du groupe.');
            this.loadCompany(this.company.id);
          },
          error: () => {
            this.alertService.open('Erreur lors du retrait du membre.');
          }
        });
      }
    });
  }

  createGroup(): void {
    this.showCreateGroupModal = true;
    this.cdr.markForCheck();
  }

  cancelCreateGroup(): void {
    this.showCreateGroupModal = false;
    this.newGroupName = '';
    this.newGroupDescription = '';
    this.selectedMemberIds = [];
    this.cdr.markForCheck();
  }

  confirmCreateGroup(): void {
    if (!this.newGroupName.trim() || !this.company?.id) return;

    const groupData = {
      name: this.newGroupName.trim(),
      description: this.newGroupDescription?.trim() || '',
      companyId: this.company.id,
      memberIds: this.selectedMemberIds
    };

    this.companyService.createGroup(groupData).subscribe({
      next: () => {
        this.alertService.open('Groupe créé avec succès.');
        this.cancelCreateGroup();
        this.loadCompany(this.company.id);
      },
      error: () => {
        this.alertService.open('Erreur lors de la création du groupe.');
      }
    });
  }

  addMemberToGroup(groupId: number): void {
    this.selectedGroupId = groupId;
    this.showAddMemberModal = true;
    this.cdr.markForCheck();
  }

  cancelAddMember(): void {
    this.showAddMemberModal = false;
    this.selectedGroupId = null;
    this.selectedUserId = null;
    this.cdr.markForCheck();
  }

  confirmAddMember(): void {
    if (!this.selectedGroupId || !this.selectedUserId) return;

    this.companyService.addUserToGroup(this.selectedGroupId, this.selectedUserId).subscribe({
      next: () => {
        this.alertService.open('Membre ajouté au groupe.');
        this.cancelAddMember();
        this.loadCompany(this.company.id);
      },
      error: () => {
        this.alertService.open('Erreur lors de l\'ajout du membre.');
      }
    });
  }

  selectAllMembers(): void {
    this.selectedMemberIds = this.availableMembersForNewGroup.map(u => u.id);
    this.cdr.markForCheck();
  }

  deselectAllMembers(): void {
    this.selectedMemberIds = [];
    this.cdr.markForCheck();
  }

  isMemberSelected(userId: number): boolean {
    return this.selectedMemberIds.includes(userId);
  }

  toggleMemberSelection(userId: number): void {
    if (this.isMemberSelected(userId)) {
      this.selectedMemberIds = this.selectedMemberIds.filter(id => id !== userId);
    } else {
      this.selectedMemberIds.push(userId);
    }
    this.cdr.markForCheck();
  }
}
