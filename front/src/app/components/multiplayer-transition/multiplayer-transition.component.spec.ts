import { ComponentFixture, TestBed } from '@angular/core/testing';
import {
  MultiplayerTransitionComponent,
  TransitionPlayer,
} from './multiplayer-transition.component';
import { NO_ERRORS_SCHEMA } from '@angular/core';

describe('MultiplayerTransitionComponent', () => {
  let component: MultiplayerTransitionComponent;
  let fixture: ComponentFixture<MultiplayerTransitionComponent>;

  const mockPlayers: TransitionPlayer[] = [
    {
      id: 1,
      username: 'Player1',
      email: 'player1@example.com',
      avatar: { shape: 'blob_circle', color: '#91DEDA' },
      score: 100,
      rank: 1,
      isCurrentUser: true,
      lastAnswerCorrect: true,
      scorePercentage: 80,
      pointsGained: 10,
    },
    {
      id: 2,
      username: 'Player2',
      score: 80,
      rank: 2,
      isCurrentUser: false,
      lastAnswerCorrect: false,
      scorePercentage: 60,
      pointsGained: 5,
    },
    {
      id: 3,
      username: 'Player3',
      score: 60,
      rank: 3,
      isCurrentUser: false,
      lastAnswerCorrect: true,
      scorePercentage: 40,
      pointsGained: 0,
    },
  ];

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MultiplayerTransitionComponent],
      schemas: [NO_ERRORS_SCHEMA],
    }).compileComponents();

    fixture = TestBed.createComponent(MultiplayerTransitionComponent);
    component = fixture.componentInstance;
    component.players = mockPlayers;
    component.questionNumber = 2;
    component.totalQuestions = 5;
    component.show = true;
    component.duration = 3000;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should sort players by score in descending order', () => {
    component.players = [
      { id: 1, username: 'Player1', score: 50, rank: 0, isCurrentUser: false, scorePercentage: 50 },
      {
        id: 2,
        username: 'Player2',
        score: 100,
        rank: 0,
        isCurrentUser: false,
        scorePercentage: 100,
      },
      { id: 3, username: 'Player3', score: 75, rank: 0, isCurrentUser: false, scorePercentage: 75 },
    ];

    component['showTransition']();

    expect(component.players[0].score).toBe(100);
    expect(component.players[1].score).toBe(75);
    expect(component.players[2].score).toBe(50);
  });

  it('should assign ranks correctly after sorting', () => {
    component.players = [
      { id: 1, username: 'Player1', score: 50, rank: 0, isCurrentUser: false, scorePercentage: 50 },
      {
        id: 2,
        username: 'Player2',
        score: 100,
        rank: 0,
        isCurrentUser: false,
        scorePercentage: 100,
      },
      { id: 3, username: 'Player3', score: 75, rank: 0, isCurrentUser: false, scorePercentage: 75 },
    ];

    component['showTransition']();

    expect(component.players[0].rank).toBe(1);
    expect(component.players[1].rank).toBe(2);
    expect(component.players[2].rank).toBe(3);
  });

  it('should return correct rank medals', () => {
    expect(component.getRankMedal(1)).toBe('🥇');
    expect(component.getRankMedal(2)).toBe('🥈');
    expect(component.getRankMedal(3)).toBe('🥉');
    expect(component.getRankMedal(4)).toBe('');
  });

  it('should return correct rank colors', () => {
    expect(component.getRankColor(1)).toBe('var(--color-accent)');
    expect(component.getRankColor(2)).toBe('#c0c0c0');
    expect(component.getRankColor(3)).toBe('#cd7f32');
    expect(component.getRankColor(4)).toBe('var(--color-text-secondary)');
  });

  it('should return correct player card classes', () => {
    const player1 = mockPlayers[0]; // rank 1, current user
    const player2 = mockPlayers[1]; // rank 2, not current user
    const player3 = mockPlayers[2]; // rank 3, not current user

    expect(component.getPlayerCardClass(player1)).toBe('player-card current-user rank-1');
    expect(component.getPlayerCardClass(player2)).toBe('player-card rank-2');
    expect(component.getPlayerCardClass(player3)).toBe('player-card rank-3');
  });

  it('should calculate progress percentage correctly', () => {
    component.questionNumber = 3;
    component.totalQuestions = 10;

    expect(component.getProgressPercentage()).toBe(30);
  });

  it('should return default avatar style when player has no avatar', () => {
    const playerWithoutAvatar = { ...mockPlayers[0], avatar: undefined };
    const style = component.getAvatarStyle(playerWithoutAvatar);

    expect(style.background).toBe(
      'linear-gradient(135deg, var(--color-secondary), var(--color-secondary-dark))'
    );
  });

  it('should return correct avatar style when player has avatar', () => {
    const playerWithAvatar = mockPlayers[0];
    const style = component.getAvatarStyle(playerWithAvatar);

    expect(style.background).toBe('linear-gradient(135deg, #91DEDA, #5BC0BE)');
  });

  it('should return correct darker color for known colors', () => {
    expect(component['getDarkerColor']('#91DEDA')).toBe('#5BC0BE');
    expect(component['getDarkerColor']('#FAA24B')).toBe('#E67E22');
    expect(component['getDarkerColor']('#D30D4C')).toBe('#B91C5C');
    expect(component['getDarkerColor']('#000000')).toBe('#000000');
  });

  it('should return correct avatar shape', () => {
    const playerWithAvatar = mockPlayers[0];
    const playerWithoutAvatar = { ...mockPlayers[0], avatar: undefined };

    expect(component.getAvatarShape(playerWithAvatar)).toBe('blob_circle');
    expect(component.getAvatarShape(playerWithoutAvatar)).toBe('blob_circle');
  });

  it('should clear timeout on destroy', () => {
    spyOn(window, 'clearTimeout');
    component['hideTimeout'] = 123 as any;

    component.ngOnDestroy();

    expect(window.clearTimeout).toHaveBeenCalledWith(123);
  });

  it('should not clear timeout if none exists', () => {
    spyOn(window, 'clearTimeout');
    component['hideTimeout'] = undefined;

    component.ngOnDestroy();

    expect(window.clearTimeout).not.toHaveBeenCalled();
  });
});
