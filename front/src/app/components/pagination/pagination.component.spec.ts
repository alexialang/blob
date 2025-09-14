import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PaginationComponent } from './pagination.component';

describe('PaginationComponent', () => {
  let component: PaginationComponent;
  let fixture: ComponentFixture<PaginationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PaginationComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PaginationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.totalItems).toBe(0);
    expect(component.pageSize).toBe(10);
    expect(component.page).toBe(1);
    expect(component.activePadding).toBe(2);
  });

  it('should calculate totalPages correctly', () => {
    component.totalItems = 25;
    component.pageSize = 10;
    
    expect(component.totalPages).toBe(3);
  });

  it('should return 1 for totalPages when totalItems is 0', () => {
    component.totalItems = 0;
    component.pageSize = 10;
    
    expect(component.totalPages).toBe(1);
  });

  it('should calculate tuiIndex correctly', () => {
    component.totalItems = 25;
    component.pageSize = 10;
    component.page = 2;
    
    expect(component.tuiIndex).toBe(1);
  });

  it('should clamp tuiIndex to valid range', () => {
    component.totalItems = 25;
    component.pageSize = 10;
    component.page = 5; // Beyond totalPages
    
    expect(component.tuiIndex).toBe(2); // Should be clamped to max index
  });

  it('should emit pageChange when onTuiIndexChange is called', () => {
    spyOn(component.pageChange, 'emit');
    
    component.onTuiIndexChange(2); // Index 2 = page 3
    
    expect(component.pageChange.emit).toHaveBeenCalledWith(3);
  });
});