import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LeaderboardModalComponent } from './leaderboard-modal.component';

describe('LeaderboardModalComponent', () => {
  let component: LeaderboardModalComponent;
  let fixture: ComponentFixture<LeaderboardModalComponent>;

  const mockLeaderboard = [
    { username: 'Player1', score: 100, rank: 1 },
    { username: 'Player2', score: 80, rank: 2 },
    { username: 'Player3', score: 60, rank: 3 },
  ];

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LeaderboardModalComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(LeaderboardModalComponent);
    component = fixture.componentInstance;
    component.leaderboard = mockLeaderboard;
    component.quizTitle = 'Test Quiz';
    component.playerRank = 2;
    component.totalScore = 80;
    component.totalPlayers = 3;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should emit close event', () => {
    spyOn(component.close, 'emit');
    component.closeModal();
    expect(component.close.emit).toHaveBeenCalled();
  });

  it('should emit replay event', () => {
    spyOn(component.replay, 'emit');
    component.onReplay();
    expect(component.replay.emit).toHaveBeenCalled();
  });

  it('should emit share event', () => {
    spyOn(component.share, 'emit');
    component.onShare();
    expect(component.share.emit).toHaveBeenCalled();
  });

  it('should return correct medal icons', () => {
    expect(component.getMedalIcon(1)).toBe('🥇');
    expect(component.getMedalIcon(2)).toBe('🥈');
    expect(component.getMedalIcon(3)).toBe('🥉');
    expect(component.getMedalIcon(4)).toBe('');
  });

  it('should return correct player levels based on score', () => {
    expect(component.getPlayerLevel(90)).toBe('Expert');
    expect(component.getPlayerLevel(70)).toBe('Avancé');
    expect(component.getPlayerLevel(50)).toBe('Intermédiaire');
    expect(component.getPlayerLevel(30)).toBe('Débutant');
    expect(component.getPlayerLevel(10)).toBe('Novice');
  });

  it('should return correct position suffix', () => {
    expect(component.getPositionSuffix(1)).toBe('er');
    expect(component.getPositionSuffix(2)).toBe('ème');
    expect(component.getPositionSuffix(11)).toBe('ème');
    expect(component.getPositionSuffix(12)).toBe('ème');
    expect(component.getPositionSuffix(13)).toBe('ème');
    expect(component.getPositionSuffix(21)).toBe('er');
    expect(component.getPositionSuffix(22)).toBe('ème');
  });

  it('should have default input values', () => {
    const newComponent = new LeaderboardModalComponent();
    expect(newComponent.isVisible).toBe(false);
    expect(newComponent.leaderboard).toEqual([]);
    expect(newComponent.quizTitle).toBe('');
    expect(newComponent.playerRank).toBe(1);
    expect(newComponent.totalScore).toBe(0);
    expect(newComponent.totalPlayers).toBe(1);
  });
});
