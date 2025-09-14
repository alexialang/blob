import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddMemberModalComponent } from './add-member-modal.component';
import { CompanyService } from '../../services/company.service';
import { of, throwError } from 'rxjs';

describe('AddMemberModalComponent', () => {
  let component: AddMemberModalComponent;
  let fixture: ComponentFixture<AddMemberModalComponent>;
  let mockCompanyService: jasmine.SpyObj<CompanyService>;

  const mockUsers = [
    { id: 1, firstName: 'John', lastName: 'Doe', email: 'john@example.com', pseudo: 'john_doe', roles: [], isVerified: true, companyId: 1, companyName: 'Test Company' },
    { id: 2, firstName: 'Jane', lastName: 'Smith', email: 'jane@example.com', pseudo: 'jane_smith', roles: [], isVerified: true, companyId: 1, companyName: 'Test Company' }
  ];

  beforeEach(async () => {
    mockCompanyService = jasmine.createSpyObj('CompanyService', ['getAvailableUsers', 'assignUserToCompany']);
    mockCompanyService.getAvailableUsers.and.returnValue(of(mockUsers));

    await TestBed.configureTestingModule({
      imports: [AddMemberModalComponent],
      providers: [
        { provide: CompanyService, useValue: mockCompanyService }
      ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AddMemberModalComponent);
    component = fixture.componentInstance;
    component.companyId = 1;
    // Don't call detectChanges() to avoid ngOnInit
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load available users on init', () => {
    component.ngOnInit();
    expect(mockCompanyService.getAvailableUsers).toHaveBeenCalledWith(1);
  });

  it('should filter users based on search term', () => {
    component.availableUsers$.next(mockUsers);
    component.onSearchChange('john');
    
    component.filteredUsers$.subscribe(filtered => {
      expect(filtered.length).toBe(1);
      expect(filtered[0].firstName).toBe('John');
    });
  });

  it('should select user', () => {
    component.selectUser(1);
    expect(component.selectedUserId).toBe(1);
    expect(component.errorMessage).toBe('');
  });

  it('should show error when no user selected for adding member', () => {
    component.addMember();
    expect(component.errorMessage).toBe('Veuillez sélectionner un utilisateur');
  });

  it('should add member successfully', () => {
    component.selectedUserId = 1;
    const mockResult = { success: true, user: mockUsers[0], message: 'Success' };
    mockCompanyService.assignUserToCompany.and.returnValue(of(mockResult));
    spyOn(component.memberAdded, 'emit');
    spyOn(component, 'closeModal');
    
    component.addMember();
    
    expect(mockCompanyService.assignUserToCompany).toHaveBeenCalledWith(1, 1, ['ROLE_USER'], []);
    expect(component.memberAdded.emit).toHaveBeenCalledWith(mockUsers[0]);
    expect(component.closeModal).toHaveBeenCalled();
  });

  it('should handle error when adding member fails', () => {
    component.selectedUserId = 1;
    mockCompanyService.assignUserToCompany.and.returnValue(throwError('Error'));
    
    component.addMember();
    
    expect(component.errorMessage).toBe("Erreur lors de l'ajout du membre");
    expect(component.loading).toBe(false);
  });

  it('should emit modal closed event', () => {
    spyOn(component.modalClosed, 'emit');
    component.closeModal();
    expect(component.modalClosed.emit).toHaveBeenCalled();
  });

  it('should track users by id', () => {
    const result = component.trackByUserId(0, mockUsers[0]);
    expect(result).toBe(1);
  });

  it('should have default values', () => {
    expect(component.selectedUserId).toBeNull();
    expect(component.loading).toBe(false);
    expect(component.errorMessage).toBe('');
    expect(component.searchTerm).toBe('');
    expect(component.selectedRoles['ROLE_USER']).toBe(true);
  });
});