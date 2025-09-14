import { ComponentFixture, TestBed } from '@angular/core/testing';
import { StatisticsChartsComponent } from './statistics-charts.component';
import { HttpClientTestingModule } from '@angular/common/http/testing';

describe('StatisticsChartsComponent', () => {
  let component: StatisticsChartsComponent;
  let fixture: ComponentFixture<StatisticsChartsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [StatisticsChartsComponent, HttpClientTestingModule],
    }).compileComponents();

    fixture = TestBed.createComponent(StatisticsChartsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component).toBeDefined();
  });
});