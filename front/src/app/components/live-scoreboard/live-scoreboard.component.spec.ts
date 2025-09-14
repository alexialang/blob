import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LiveScoreboardComponent, LivePlayerScore } from './live-scoreboard.component';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { NO_ERRORS_SCHEMA, SimpleChange } from '@angular/core';

describe('LiveScoreboardComponent', () => {
  let component: LiveScoreboardComponent;
  let fixture: ComponentFixture<LiveScoreboardComponent>;

  const mockPlayers: LivePlayerScore[] = [
    {
      username: 'Player1',
      score: 100,
      isCurrentUser: true,
      rank: 1,
      isOnline: true,
      lastAnswerCorrect: true
    },
    {
      username: 'Player2',
      score: 80,
      isCurrentUser: false,
      rank: 2,
      isOnline: true,
      lastAnswerCorrect: false
    },
    {
      username: 'Player3',
      score: 60,
      isCurrentUser: false,
      rank: 3,
      isOnline: false,
      lastAnswerCorrect: true
    }
  ];

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LiveScoreboardComponent, HttpClientTestingModule],
      schemas: [NO_ERRORS_SCHEMA]
    }).compileComponents();

    fixture = TestBed.createComponent(LiveScoreboardComponent);
    component = fixture.componentInstance;
    component.players = mockPlayers;
    component.totalQuestions = 10;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have default values', () => {
    expect(component.players).toBeDefined();
    expect(component.currentQuestionIndex).toBe(0);
    expect(component.totalQuestions).toBe(10); // Set in beforeEach
    expect(component.showRanking).toBe(true);
  });

  it('should get player rank correctly', () => {
    const rank = component.getPlayerRank(mockPlayers[0]);
    expect(rank).toBe(1);
  });

  it('should calculate score percentage correctly', () => {
    const percentage = component.getScorePercentage(mockPlayers[0]);
    expect(percentage).toBe(100); // 100 / (10 * 10) * 100 = 100%
  });

  it('should return 0 percentage when totalQuestions is 0', () => {
    component.totalQuestions = 0;
    const percentage = component.getScorePercentage(mockPlayers[0]);
    expect(percentage).toBe(0);
  });

  it('should cap score percentage at 100', () => {
    const highScorePlayer = { ...mockPlayers[0], score: 150 };
    const percentage = component.getScorePercentage(highScorePlayer);
    expect(percentage).toBe(100);
  });

  it('should get avatar path correctly', () => {
    const playerWithAvatar = { ...mockPlayers[0], avatar: 'blob_circle.svg' };
    const path = component.getAvatarPath(playerWithAvatar);
    expect(path).toBe('/assets/avatars/blob_circle.svg');
  });

  it('should return default avatar path when no avatar', () => {
    const playerWithoutAvatar = { ...mockPlayers[0], avatar: undefined };
    const path = component.getAvatarPath(playerWithoutAvatar);
    expect(path).toBe('/assets/avatars/blob_circle.svg');
  });

  it('should detect rank changes correctly', () => {
    const player = { ...mockPlayers[0], previousRank: 2 };
    const hasChanged = component.hasRankChanged(player);
    expect(hasChanged).toBe(true);
  });

  it('should return false for rank change when no previous rank', () => {
    const player = { ...mockPlayers[0], previousRank: undefined };
    const hasChanged = component.hasRankChanged(player);
    expect(hasChanged).toBe(false);
  });

  it('should return false for rank change when rank is same', () => {
    const player = { ...mockPlayers[0], previousRank: 1 };
    const hasChanged = component.hasRankChanged(player);
    expect(hasChanged).toBe(false);
  });

  it('should get rank change as up', () => {
    const player = { ...mockPlayers[0], previousRank: 2 };
    const change = component.getRankChange(player);
    expect(change).toBe('up');
  });

  it('should get rank change as down', () => {
    const player = { ...mockPlayers[1], previousRank: 1 };
    const change = component.getRankChange(player);
    expect(change).toBe('down');
  });

  it('should get rank change as same', () => {
    const player = { ...mockPlayers[0], previousRank: 1 };
    const change = component.getRankChange(player);
    expect(change).toBe('same');
  });

  it('should handle ngOnChanges with players change', () => {
    const newPlayers = [...mockPlayers];
    spyOn(component as any, 'assignAvatarsToPlayers');
    spyOn(component as any, 'detectRankChanges');
    spyOn(component as any, 'sortPlayersByScore');

    component.ngOnChanges({ players: { currentValue: newPlayers, previousValue: [], firstChange: false, isFirstChange: () => false } as SimpleChange });

    expect((component as any).assignAvatarsToPlayers).toHaveBeenCalled();
    expect((component as any).detectRankChanges).toHaveBeenCalled();
    expect((component as any).sortPlayersByScore).toHaveBeenCalled();
  });

  it('should assign avatars to players', () => {
    const players: LivePlayerScore[] = [
      { username: 'Player1', score: 100, isCurrentUser: true, rank: 1, isOnline: true },
      { username: 'Player2', score: 80, isCurrentUser: false, rank: 2, isOnline: true }
    ];
    component.players = players;
    
    (component as any).assignAvatarsToPlayers();
    
    expect(players[0].avatar).toBeDefined();
    expect(players[1].avatar).toBeDefined();
  });
});