import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PaginationComponent } from './pagination.component';

describe('PaginationComponent', () => {
  let component: PaginationComponent;
  let fixture: ComponentFixture<PaginationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PaginationComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(PaginationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.page).toBe(1);
    expect(component.totalItems).toBe(0);
    expect(component.pageSize).toBe(10);
    expect(component.activePadding).toBe(2);
  });

  it('should calculate total pages', () => {
    component.totalItems = 100;
    component.pageSize = 10;
    expect(component.totalPages).toBe(10);
    
    component.totalItems = 95;
    component.pageSize = 10;
    expect(component.totalPages).toBe(10);
  });

  it('should calculate tui index', () => {
    component.page = 3;
    component.totalItems = 100;
    component.pageSize = 10;
    expect(component.tuiIndex).toBe(2);
  });

  it('should emit page change on tui index change', () => {
    spyOn(component.pageChange, 'emit');
    component.onTuiIndexChange(1);
    expect(component.pageChange.emit).toHaveBeenCalledWith(2);
  });

  it('should not emit page change if page is same', () => {
    component.page = 2;
    spyOn(component.pageChange, 'emit');
    component.onTuiIndexChange(1);
    expect(component.pageChange.emit).not.toHaveBeenCalled();
  });

  it('should handle edge cases for tui index', () => {
    component.page = 0;
    component.totalItems = 50;
    component.pageSize = 10;
    expect(component.tuiIndex).toBe(0);
    
    component.page = 10;
    component.totalItems = 50;
    component.pageSize = 10;
    expect(component.tuiIndex).toBe(4);
  });
});