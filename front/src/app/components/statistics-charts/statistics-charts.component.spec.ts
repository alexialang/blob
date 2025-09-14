import { ComponentFixture, TestBed } from '@angular/core/testing';
import { StatisticsChartsComponent } from './statistics-charts.component';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';

describe('StatisticsChartsComponent', () => {
  let component: StatisticsChartsComponent;
  let fixture: ComponentFixture<StatisticsChartsComponent>;

  const mockUserStatistics = {
    scoreHistory: [
      { date: '2024-01-01', score: 80 },
      { date: '2024-01-02', score: 85 }
    ],
    categoryStats: [
      { category: 'Math', correctAnswers: 8, totalAnswers: 10 },
      { category: 'Science', correctAnswers: 6, totalAnswers: 8 }
    ]
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [StatisticsChartsComponent, HttpClientTestingModule],
      schemas: [NO_ERRORS_SCHEMA]
    }).compileComponents();

    fixture = TestBed.createComponent(StatisticsChartsComponent);
    component = fixture.componentInstance;
    component.userStatistics = mockUserStatistics;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.userStatistics).toBeDefined();
    expect(component.userStatistics).toEqual(mockUserStatistics);
  });

  it('should initialize with null userStatistics by default', () => {
    const newComponent = TestBed.createComponent(StatisticsChartsComponent).componentInstance;
    expect(newComponent.userStatistics).toBe(null);
  });

  it('should handle ngOnDestroy without charts', () => {
    expect(() => component.ngOnDestroy()).not.toThrow();
  });

  it('should handle ngOnDestroy with existing charts', () => {
    // Mock chart instances
    component['scoreEvolutionChartInstance'] = { destroy: jasmine.createSpy('destroy') };
    component['categoryChartInstance'] = { destroy: jasmine.createSpy('destroy') };

    component.ngOnDestroy();

    expect(component['scoreEvolutionChartInstance'].destroy).toHaveBeenCalled();
    expect(component['categoryChartInstance'].destroy).toHaveBeenCalled();
    expect(component['chartsCreated']).toBe(false);
  });

  it('should handle ngOnChanges when conditions are met', () => {
    // Mock Chart and ViewChild elements
    (window as any).Chart = {};
    component['scoreChart'] = { nativeElement: { getContext: () => ({}) } } as any;
    component['categoryChart'] = { nativeElement: { getContext: () => ({}) } } as any;
    
    spyOn(component as any, 'createCharts');

    component.ngOnChanges();

    expect((component as any).createCharts).toHaveBeenCalled();
    expect(component['chartsCreated']).toBe(true);
  });

  it('should not create charts in ngOnChanges when Chart is undefined', () => {
    (window as any).Chart = undefined;
    component['scoreChart'] = { nativeElement: { getContext: () => ({}) } } as any;
    component['categoryChart'] = { nativeElement: { getContext: () => ({}) } } as any;
    
    spyOn(component as any, 'createCharts');

    component.ngOnChanges();

    expect((component as any).createCharts).not.toHaveBeenCalled();
  });

  it('should not create charts in ngOnChanges when userStatistics is null', () => {
    component.userStatistics = null;
    (window as any).Chart = {};
    component['scoreChart'] = { nativeElement: { getContext: () => ({}) } } as any;
    component['categoryChart'] = { nativeElement: { getContext: () => ({}) } } as any;
    
    spyOn(component as any, 'createCharts');

    component.ngOnChanges();

    expect((component as any).createCharts).not.toHaveBeenCalled();
  });

  it('should destroy existing charts in ngOnChanges', () => {
    (window as any).Chart = {};
    component['scoreChart'] = { nativeElement: { getContext: () => ({}) } } as any;
    component['categoryChart'] = { nativeElement: { getContext: () => ({}) } } as any;
    
    const mockScoreChart = { destroy: jasmine.createSpy('destroy') };
    const mockCategoryChart = { destroy: jasmine.createSpy('destroy') };
    component['scoreEvolutionChartInstance'] = mockScoreChart;
    component['categoryChartInstance'] = mockCategoryChart;
    
    spyOn(component as any, 'createCharts');

    component.ngOnChanges();

    expect(mockScoreChart.destroy).toHaveBeenCalled();
    expect(mockCategoryChart.destroy).toHaveBeenCalled();
    expect(component['scoreEvolutionChartInstance']).toBe(null);
    expect(component['categoryChartInstance']).toBe(null);
  });

  it('should load Chart.js when not already loaded', async () => {
    // Mock document.createElement and appendChild
    const mockScript = { onload: null as any, src: '' };
    spyOn(document, 'createElement').and.returnValue(mockScript as any);
    spyOn(document.head, 'appendChild');

    (window as any).Chart = undefined;

    const loadPromise = (component as any).loadChartJs();
    
    // Simulate script load
    setTimeout(() => {
      if (mockScript.onload) {
        mockScript.onload();
      }
    }, 10);

    await loadPromise;

    expect(document.createElement).toHaveBeenCalledWith('script');
    expect(mockScript.src).toBe('https://cdn.jsdelivr.net/npm/chart.js');
    expect(document.head.appendChild).toHaveBeenCalledWith(mockScript as any);
  });

  it('should resolve immediately when Chart is already loaded', async () => {
    (window as any).Chart = {};

    const result = await (component as any).loadChartJs();
    
    expect(result).toBeUndefined();
  });

  it('should create both charts when createCharts is called', () => {
    spyOn(component as any, 'createScoreEvolutionChart');
    spyOn(component as any, 'createCategoryPerformanceChart');

    (component as any).createCharts();

    expect((component as any).createScoreEvolutionChart).toHaveBeenCalled();
    expect((component as any).createCategoryPerformanceChart).toHaveBeenCalled();
  });
});