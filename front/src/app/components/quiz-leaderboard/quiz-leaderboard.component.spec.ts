import { ComponentFixture, TestBed } from '@angular/core/testing';
import { QuizLeaderboardComponent } from './quiz-leaderboard.component';

describe('QuizLeaderboardComponent', () => {
  let component: QuizLeaderboardComponent;
  let fixture: ComponentFixture<QuizLeaderboardComponent>;

  const mockLeaderboard = [
    {
      username: 'Player1',
      score: 100,
      rank: 1,
      avatar: 'avatar1.svg'
    },
    {
      username: 'Player2',
      score: 85,
      rank: 2,
      avatar: 'avatar2.svg'
    },
    {
      username: 'Player3',
      score: 70,
      rank: 3,
      avatar: 'avatar3.svg'
    }
  ];

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [QuizLeaderboardComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(QuizLeaderboardComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.leaderboard).toEqual([]);
    expect(component.quizTitle).toBe('');
    expect(component.playerRank).toBe(1);
    expect(component.totalScore).toBe(0);
    expect(component.totalPlayers).toBe(0);
  });

  it('should accept input properties', () => {
    component.leaderboard = mockLeaderboard;
    component.quizTitle = 'Test Quiz';
    component.playerRank = 2;
    component.totalScore = 85;
    component.totalPlayers = 10;

    expect(component.leaderboard).toEqual(mockLeaderboard);
    expect(component.quizTitle).toBe('Test Quiz');
    expect(component.playerRank).toBe(2);
    expect(component.totalScore).toBe(85);
    expect(component.totalPlayers).toBe(10);
  });

  it('should emit close event', () => {
    spyOn(component.close, 'emit');
    
    component.onClose();
    
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

  it('should handle empty leaderboard', () => {
    component.leaderboard = [];
    
    expect(component.leaderboard).toEqual([]);
  });

  it('should handle null leaderboard', () => {
    component.leaderboard = null as any;
    
    expect(component.leaderboard).toBeNull();
  });

  it('should handle single player leaderboard', () => {
    const singlePlayerLeaderboard = [mockLeaderboard[0]];
    component.leaderboard = singlePlayerLeaderboard;
    
    expect(component.leaderboard.length).toBe(1);
    expect(component.leaderboard[0]).toEqual(mockLeaderboard[0]);
  });

  it('should handle large leaderboard', () => {
    const largeLeaderboard = Array.from({ length: 50 }, (_, i) => ({
      username: `Player${i + 1}`,
      score: 100 - i,
      rank: i + 1,
      avatar: `avatar${i + 1}.svg`
    }));
    
    component.leaderboard = largeLeaderboard;
    
    expect(component.leaderboard.length).toBe(50);
    expect(component.leaderboard[0].rank).toBe(1);
    expect(component.leaderboard[49].rank).toBe(50);
  });

  it('should handle zero values', () => {
    component.playerRank = 0;
    component.totalScore = 0;
    component.totalPlayers = 0;
    
    expect(component.playerRank).toBe(0);
    expect(component.totalScore).toBe(0);
    expect(component.totalPlayers).toBe(0);
  });

  it('should handle negative values', () => {
    component.playerRank = -1;
    component.totalScore = -10;
    component.totalPlayers = -5;
    
    expect(component.playerRank).toBe(-1);
    expect(component.totalScore).toBe(-10);
    expect(component.totalPlayers).toBe(-5);
  });

  it('should handle long quiz title', () => {
    const longTitle = 'This is a very long quiz title that might be used to test the component behavior with long text content';
    component.quizTitle = longTitle;
    
    expect(component.quizTitle).toBe(longTitle);
  });

  it('should handle special characters in quiz title', () => {
    const specialTitle = 'Quiz with special chars: @#$%^&*()_+-=[]{}|;:,.<>?';
    component.quizTitle = specialTitle;
    
    expect(component.quizTitle).toBe(specialTitle);
  });

  it('should handle unicode characters in quiz title', () => {
    const unicodeTitle = 'Quiz avec des caractères français: éèêëçàâä';
    component.quizTitle = unicodeTitle;
    
    expect(component.quizTitle).toBe(unicodeTitle);
  });
});

