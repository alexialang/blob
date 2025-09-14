import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LiveScoreboardComponent } from './live-scoreboard.component';
import { HttpClientTestingModule } from '@angular/common/http/testing';

describe('LiveScoreboardComponent', () => {
  let component: LiveScoreboardComponent;
  let fixture: ComponentFixture<LiveScoreboardComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LiveScoreboardComponent, HttpClientTestingModule],
    }).compileComponents();

    fixture = TestBed.createComponent(LiveScoreboardComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.players).toBeDefined();
    expect(component.players).toEqual([]);
  });
});