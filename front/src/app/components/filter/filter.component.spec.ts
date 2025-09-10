import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

import { FilterComponent } from './filter.component';

describe('FilterComponent', () => {
  let component: FilterComponent;
  let fixture: ComponentFixture<FilterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FilterComponent, FormsModule, CommonModule],
    }).compileComponents();

    fixture = TestBed.createComponent(FilterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should emit filter change on input change', () => {
    spyOn(component.filterChange, 'emit');

    component.onFilterChange('test', 'value');

    expect(component.filters['test']).toBe('value');
    expect(component.filterChange.emit).toHaveBeenCalledWith(component.filters);
  });

  it('should handle multiple filter options', () => {
    component.filterOptions = [
      { label: 'Category', options: ['Option1', 'Option2'] },
      { label: 'Difficulty', options: ['Easy', 'Hard'] },
    ];

    fixture.detectChanges();

    expect(component.filterOptions.length).toBe(2);
  });
});
