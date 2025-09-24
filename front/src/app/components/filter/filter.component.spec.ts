import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FilterComponent } from './filter.component';

describe('FilterComponent', () => {
  let component: FilterComponent;
  let fixture: ComponentFixture<FilterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FilterComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(FilterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.filterOptions).toEqual([]);
    expect(component.filters).toEqual({});
  });

  it('should emit filter change when filter is updated', () => {
    spyOn(component.filterChange, 'emit');
    component.onFilterChange('difficulty', 'easy');
    expect(component.filters['difficulty']).toBe('easy');
    expect(component.filterChange.emit).toHaveBeenCalledWith({ difficulty: 'easy' });
  });

  it('should update multiple filters', () => {
    spyOn(component.filterChange, 'emit');
    component.onFilterChange('difficulty', 'medium');
    component.onFilterChange('category', 'science');
    expect(component.filters['difficulty']).toBe('medium');
    expect(component.filters['category']).toBe('science');
    expect(component.filterChange.emit).toHaveBeenCalledTimes(2);
  });
});
