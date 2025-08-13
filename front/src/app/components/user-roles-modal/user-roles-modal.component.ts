import { Component, Input, Output, EventEmitter, OnInit, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

export interface UserRole {
  id: number;
  name: string;
  description: string;
  permissions: string[];
}

export interface UserWithRoles {
  id: number;
  name: string;
  email: string;
  roles: string[];
  permissions: string[];
}

@Component({
  selector: 'app-user-roles-modal',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule
  ],
  templateUrl: './user-roles-modal.component.html',
  styleUrls: ['./user-roles-modal.component.scss']
})
export class UserRolesModalComponent implements OnInit, OnChanges {
  @Input() user: UserWithRoles | null = null;
  @Input() isOpen = false;
  @Input() availableRoles: UserRole[] = [];
  @Input() availablePermissions: string[] = [];
  
  @Output() closeModal = new EventEmitter<void>();
  @Output() saveChanges = new EventEmitter<{
    userId: number;
    roles: string[];
    permissions: string[];
  }>();

  selectedRoles: Set<string> = new Set();
  selectedPermissions: Set<string> = new Set();

  ngOnInit() {
    if (this.user) {
      this.selectedRoles = new Set(this.user.roles);
      this.selectedPermissions = new Set(this.user.permissions);
    }
  }

  ngOnChanges(changes: SimpleChanges) {
    if (this.user && this.isOpen) {
      this.selectedRoles = new Set(this.user.roles);
      this.selectedPermissions = new Set(this.user.permissions);
    }
  }

  toggleRole(role: string) {
    if (this.selectedRoles.has(role)) {
      this.selectedRoles.delete(role);
      
      if (role === 'ROLE_ADMIN') {
        this.selectedPermissions.clear();
      }
    } else {
      this.selectedRoles.add(role);
      
      if (role === 'ROLE_ADMIN') {
        this.availablePermissions.forEach(perm => this.selectedPermissions.add(perm));
      }
    }
  }

  togglePermission(permission: string) {
    if (this.selectedRoles.has('ROLE_ADMIN')) {
      return;
    }

    if (this.selectedPermissions.has(permission)) {
      this.selectedPermissions.delete(permission);
    } else {
      this.selectedPermissions.add(permission);
    }
  }

  isRoleSelected(role: string): boolean {
    return this.selectedRoles.has(role);
  }

  isPermissionSelected(permission: string): boolean {
    return this.selectedPermissions.has(permission);
  }

  isPermissionDisabled(): boolean {
    return this.selectedRoles.has('ROLE_ADMIN');
  }

  onSave() {
    if (this.user) {
      this.saveChanges.emit({
        userId: this.user.id,
        roles: Array.from(this.selectedRoles),
        permissions: Array.from(this.selectedPermissions)
      });
    }
  }

  onCancel() {
    this.closeModal.emit();
  }

  getPermissionLabel(permission: string): string {
    const labels: {[key: string]: string} = {
      'CREATE_QUIZ': 'Créer des quiz',
      'MANAGE_USERS': 'Gérer les utilisateurs',
      'VIEW_RESULTS': 'Voir les résultats'
    };
    return labels[permission] || permission;
  }

  getRoleLabel(role: string): string {
    const labels: {[key: string]: string} = {
      'ROLE_ADMIN': 'Administrateur',
      'ROLE_USER': 'Utilisateur'
    };
    return labels[role] || role;
  }

  getRoleDescription(role: string): string {
    const roleObj = this.availableRoles.find(r => r.name === role);
    return roleObj?.description || '';
  }
}
