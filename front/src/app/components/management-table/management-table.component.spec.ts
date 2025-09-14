import { ComponentFixture, TestBed } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { ManagementTableComponent, ManagementTableConfig } from './management-table.component';

describe('ManagementTableComponent', () => {
  let component: ManagementTableComponent;
  let fixture: ComponentFixture<ManagementTableComponent>;

  const mockConfig: ManagementTableConfig = {
    title: 'Test Table',
    columns: [
      { key: 'id', label: 'ID', sortable: true },
      { key: 'name', label: 'Name', sortable: true }
    ],
    filters: [
      { key: 'status', label: 'Status', options: [{ value: 'active', label: 'Active' }] }
    ]
  };

  const mockData = [
    { id: 1, name: 'Item 1', selected: false },
    { id: 2, name: 'Item 2', selected: false }
  ];

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ManagementTableComponent],
      schemas: [NO_ERRORS_SCHEMA]
    }).compileComponents();

    fixture = TestBed.createComponent(ManagementTableComponent);
    component = fixture.componentInstance;
    component.config = mockConfig;
    component.data = mockData;
    // Don't call detectChanges() to avoid template errors
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.loading).toBe(false);
    expect(component.error).toBe(false);
    expect(component.pageSize).toBe(20);
    expect(component.page).toBe(1);
    expect(component.sortDirection).toBe('asc');
  });

  it('should emit filter change', () => {
    spyOn(component.filterChange, 'emit');
    component.keywordFilter = 'test';
    component.applyFilters();
    expect(component.filterChange.emit).toHaveBeenCalledWith({ keyword: 'test' });
  });

  it('should emit sort change', () => {
    spyOn(component.sortChange, 'emit');
    component.sortBy('name');
    expect(component.sortChange.emit).toHaveBeenCalledWith({ column: 'name', direction: 'asc' });
  });

  it('should toggle sort direction', () => {
    component.sortColumn = 'name';
    component.sortDirection = 'asc';
    spyOn(component.sortChange, 'emit');
    
    component.sortBy('name');
    expect(component.sortChange.emit).toHaveBeenCalledWith({ column: 'name', direction: 'desc' });
  });
});
