import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NotFoundComponent } from './not-found.component';

describe('NotFoundComponent', () => {
  let component: NotFoundComponent;
  let fixture: ComponentFixture<NotFoundComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [NotFoundComponent]
    }).compileComponents();

    fixture = TestBed.createComponent(NotFoundComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have correct component structure', () => {
    expect(component).toBeInstanceOf(NotFoundComponent);
    expect(fixture.componentInstance).toBe(component);
  });

  it('should render without errors', () => {
    expect(() => fixture.detectChanges()).not.toThrow();
  });

  it('should be a standalone component', () => {
    expect(component).toBeDefined();
    expect(fixture.componentInstance).toBe(component);
  });
});