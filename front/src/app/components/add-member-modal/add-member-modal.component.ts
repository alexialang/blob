import { Component, EventEmitter, Input, OnInit, Output, OnDestroy } from '@angular/core';
import { CompanyService } from '../../services/company.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { BehaviorSubject, Subject, takeUntil } from 'rxjs';

@Component({
  selector: 'app-add-member-modal',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule
  ],
  templateUrl: './add-member-modal.component.html',
  styleUrls: ['./add-member-modal.component.scss']
})
export class AddMemberModalComponent implements OnInit, OnDestroy {
  @Input() companyId!: number;
  @Output() memberAdded = new EventEmitter<any>();
  @Output() modalClosed = new EventEmitter<void>();

  availableUsers$ = new BehaviorSubject<any[]>([]);
  selectedUserId: number | null = null;
  selectedRoles: { [key: string]: boolean } = {
    'ROLE_USER': true
  };
  selectedPermissions: { [key: string]: boolean } = {};
  loading = false;
  errorMessage = '';

  private destroy$ = new Subject<void>();

  constructor(private companyService: CompanyService) {}

  ngOnInit(): void {
    this.loadAvailableUsers();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  loadAvailableUsers(): void {
    this.loading = true;
    this.errorMessage = '';

    this.companyService.getAvailableUsers(this.companyId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (users: any[]) => {
          this.availableUsers$.next(users);
          this.loading = false;
        },
        error: (error: any) => {
          this.errorMessage = 'Erreur lors du chargement des utilisateurs disponibles';
          this.loading = false;
        }
      });
  }

  selectUser(userId: number): void {
    this.selectedUserId = userId;
    this.errorMessage = '';
  }

  addMember(): void {
    if (!this.selectedUserId) {
      this.errorMessage = 'Veuillez sélectionner un utilisateur';
      return;
    }

    const roles = Object.keys(this.selectedRoles).filter(role => this.selectedRoles[role]);
    const permissions = Object.keys(this.selectedPermissions).filter(perm => this.selectedPermissions[perm]);

    this.loading = true;
    this.errorMessage = '';

    this.companyService.assignUserToCompany(this.companyId, this.selectedUserId, roles, permissions)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (result: any) => {
          this.loading = false;
          if (result.success) {
            this.memberAdded.emit(result.user);
            this.closeModal();
          } else {
            this.errorMessage = result.message || 'Erreur lors de l\'ajout du membre';
          }
        },
        error: (error: any) => {
          this.errorMessage = 'Erreur lors de l\'ajout du membre';
          this.loading = false;
        }
      });
  }

  closeModal(): void {
    this.modalClosed.emit();
  }

  trackByUserId(index: number, user: any): number {
    return user.id;
  }
}
