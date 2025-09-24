import { ComponentFixture, TestBed } from '@angular/core/testing';
import { UserRolesModalComponent, UserRole, UserWithRoles } from './user-roles-modal.component';

describe('UserRolesModalComponent', () => {
  let component: UserRolesModalComponent;
  let fixture: ComponentFixture<UserRolesModalComponent>;

  const mockUser: UserWithRoles = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    roles: ['ROLE_USER'],
    permissions: ['VIEW_RESULTS'],
  };

  const mockRoles: UserRole[] = [
    {
      id: 1,
      name: 'ROLE_ADMIN',
      description: 'Administrateur',
      permissions: ['CREATE_QUIZ', 'MANAGE_USERS', 'VIEW_RESULTS'],
    },
    {
      id: 2,
      name: 'ROLE_USER',
      description: 'Utilisateur',
      permissions: ['VIEW_RESULTS'],
    },
  ];

  const mockPermissions = ['CREATE_QUIZ', 'MANAGE_USERS', 'VIEW_RESULTS'];

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [UserRolesModalComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(UserRolesModalComponent);
    component = fixture.componentInstance;
    component.user = mockUser;
    component.availableRoles = mockRoles;
    component.availablePermissions = mockPermissions;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize selected roles and permissions from user', () => {
    expect(component.selectedRoles.has('ROLE_USER')).toBe(true);
    expect(component.selectedPermissions.has('VIEW_RESULTS')).toBe(true);
  });

  it('should toggle role selection', () => {
    component.toggleRole('ROLE_ADMIN');
    expect(component.selectedRoles.has('ROLE_ADMIN')).toBe(true);

    component.toggleRole('ROLE_ADMIN');
    expect(component.selectedRoles.has('ROLE_ADMIN')).toBe(false);
  });

  it('should clear permissions when admin role is removed', () => {
    component.selectedRoles.add('ROLE_ADMIN');
    component.selectedPermissions.add('CREATE_QUIZ');

    component.toggleRole('ROLE_ADMIN');
    expect(component.selectedPermissions.size).toBe(0);
  });

  it('should add all permissions when admin role is selected', () => {
    component.toggleRole('ROLE_ADMIN');
    expect(component.selectedPermissions.size).toBe(mockPermissions.length);
  });

  it('should toggle permission selection', () => {
    component.togglePermission('CREATE_QUIZ');
    expect(component.selectedPermissions.has('CREATE_QUIZ')).toBe(true);

    component.togglePermission('CREATE_QUIZ');
    expect(component.selectedPermissions.has('CREATE_QUIZ')).toBe(false);
  });

  it('should not toggle permission when admin role is selected', () => {
    component.selectedRoles.add('ROLE_ADMIN');
    component.selectedPermissions.clear(); // Clear existing permissions first
    component.togglePermission('CREATE_QUIZ');
    expect(component.selectedPermissions.size).toBe(0);
  });

  it('should check if role is selected', () => {
    component.selectedRoles.add('ROLE_USER');
    expect(component.isRoleSelected('ROLE_USER')).toBe(true);
    expect(component.isRoleSelected('ROLE_ADMIN')).toBe(false);
  });

  it('should check if permission is selected', () => {
    component.selectedPermissions.add('VIEW_RESULTS');
    expect(component.isPermissionSelected('VIEW_RESULTS')).toBe(true);
    expect(component.isPermissionSelected('CREATE_QUIZ')).toBe(false);
  });

  it('should check if permission is disabled', () => {
    expect(component.isPermissionDisabled()).toBe(false);

    component.selectedRoles.add('ROLE_ADMIN');
    expect(component.isPermissionDisabled()).toBe(true);
  });

  it('should emit save changes with correct data', () => {
    spyOn(component.saveChanges, 'emit');
    component.selectedRoles.clear();
    component.selectedRoles.add('ROLE_ADMIN');
    component.selectedPermissions.clear();
    component.selectedPermissions.add('CREATE_QUIZ');

    component.onSave();

    expect(component.saveChanges.emit).toHaveBeenCalledWith({
      userId: mockUser.id,
      roles: ['ROLE_ADMIN'],
      permissions: ['CREATE_QUIZ'],
    });
  });

  it('should emit close modal', () => {
    spyOn(component.closeModal, 'emit');
    component.onCancel();
    expect(component.closeModal.emit).toHaveBeenCalled();
  });

  it('should return correct permission labels', () => {
    expect(component.getPermissionLabel('CREATE_QUIZ')).toBe('Créer des quiz');
    expect(component.getPermissionLabel('MANAGE_USERS')).toBe('Gérer les utilisateurs');
    expect(component.getPermissionLabel('VIEW_RESULTS')).toBe('Voir les résultats');
    expect(component.getPermissionLabel('UNKNOWN')).toBe('UNKNOWN');
  });

  it('should return correct role labels', () => {
    expect(component.getRoleLabel('ROLE_ADMIN')).toBe('Administrateur');
    expect(component.getRoleLabel('ROLE_USER')).toBe('Utilisateur');
    expect(component.getRoleLabel('UNKNOWN')).toBe('UNKNOWN');
  });

  it('should return role description from available roles', () => {
    expect(component.getRoleDescription('ROLE_ADMIN')).toBe('Administrateur');
    expect(component.getRoleDescription('ROLE_USER')).toBe('Utilisateur');
    expect(component.getRoleDescription('UNKNOWN')).toBe('');
  });
});
