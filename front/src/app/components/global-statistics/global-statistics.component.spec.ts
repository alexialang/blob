import { ComponentFixture, TestBed } from '@angular/core/testing';
import { GlobalStatisticsComponent } from './global-statistics.component';
import { HttpClientTestingModule } from '@angular/common/http/testing';

describe('GlobalStatisticsComponent', () => {
  let component: GlobalStatisticsComponent;
  let fixture: ComponentFixture<GlobalStatisticsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [GlobalStatisticsComponent, HttpClientTestingModule],
    }).compileComponents();

    fixture = TestBed.createComponent(GlobalStatisticsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

