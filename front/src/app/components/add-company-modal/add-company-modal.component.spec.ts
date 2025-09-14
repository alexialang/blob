import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddCompanyModalComponent } from './add-company-modal.component';
import { CompanyService } from '../../services/company.service';
import { TuiDialogService, TuiAlertService } from '@taiga-ui/core';
import { of, throwError } from 'rxjs';

describe('AddCompanyModalComponent', () => {
  let component: AddCompanyModalComponent;
  let fixture: ComponentFixture<AddCompanyModalComponent>;
  let mockCompanyService: jasmine.SpyObj<CompanyService>;
  let mockDialogService: jasmine.SpyObj<TuiDialogService>;
  let mockAlertService: jasmine.SpyObj<TuiAlertService>;

  beforeEach(async () => {
    mockCompanyService = jasmine.createSpyObj('CompanyService', ['createCompany']);
    mockDialogService = jasmine.createSpyObj('TuiDialogService', ['open']);
    mockAlertService = jasmine.createSpyObj('TuiAlertService', ['open']);

    await TestBed.configureTestingModule({
      imports: [AddCompanyModalComponent],
      providers: [
        { provide: CompanyService, useValue: mockCompanyService },
        { provide: TuiDialogService, useValue: mockDialogService },
        { provide: TuiAlertService, useValue: mockAlertService }
      ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AddCompanyModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default company name', () => {
    expect(component.companyName).toBe('');
  });

  it('should emit cancelled event', () => {
    spyOn(component.cancelled, 'emit');
    component.cancel();
    expect(component.cancelled.emit).toHaveBeenCalled();
  });

  it('should not save when company name is empty', () => {
    component.companyName = '';
    spyOn(component.companyCreated, 'emit');
    mockCompanyService.createCompany.and.returnValue(of({ id: 1, name: 'Test' }));
    
    component.save();
    
    expect(mockCompanyService.createCompany).not.toHaveBeenCalled();
    expect(component.companyCreated.emit).not.toHaveBeenCalled();
  });

  it('should not save when company name is only whitespace', () => {
    component.companyName = '   ';
    spyOn(component.companyCreated, 'emit');
    mockCompanyService.createCompany.and.returnValue(of({ id: 1, name: 'Test' }));
    
    component.save();
    
    expect(mockCompanyService.createCompany).not.toHaveBeenCalled();
    expect(component.companyCreated.emit).not.toHaveBeenCalled();
  });

  it('should create company successfully', () => {
    const mockCompany = { id: 1, name: 'Test Company' };
    component.companyName = 'Test Company';
    mockCompanyService.createCompany.and.returnValue(of(mockCompany));
    mockAlertService.open.and.returnValue(of({}));
    spyOn(component.companyCreated, 'emit');
    
    component.save();
    
    expect(mockCompanyService.createCompany).toHaveBeenCalledWith({ name: 'Test Company' });
    expect(mockAlertService.open).toHaveBeenCalledWith('Entreprise créée avec succès !', { appearance: 'success' });
    expect(component.companyCreated.emit).toHaveBeenCalledWith(mockCompany);
  });

  it('should handle error when creating company fails', () => {
    component.companyName = 'Test Company';
    mockCompanyService.createCompany.and.returnValue(throwError('Error'));
    mockAlertService.open.and.returnValue(of({}));
    spyOn(console, 'error');
    
    component.save();
    
    expect(mockCompanyService.createCompany).toHaveBeenCalledWith({ name: 'Test Company' });
    expect(mockAlertService.open).toHaveBeenCalledWith("Erreur lors de la création de l'entreprise", { appearance: 'error' });
    expect(console.error).toHaveBeenCalledWith('Erreur création entreprise:', 'Error');
  });

  it('should trim company name when saving', () => {
    const mockCompany = { id: 1, name: 'Test Company' };
    component.companyName = '  Test Company  ';
    mockCompanyService.createCompany.and.returnValue(of(mockCompany));
    mockAlertService.open.and.returnValue(of({}));
    
    component.save();
    
    expect(mockCompanyService.createCompany).toHaveBeenCalledWith({ name: 'Test Company' });
  });
});